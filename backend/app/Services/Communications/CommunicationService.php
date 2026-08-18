<?php

namespace App\Services\Communications;

use App\Contracts\Marketing\EmailProviderInterface;
use App\Contracts\Marketing\SmsProviderInterface;
use App\Contracts\Marketing\WhatsAppProviderInterface;
use App\Models\CommunicationMessage;
use App\Models\CommunicationRecipient;
use App\Models\MailingList;
use App\Models\MailingListMember;
use App\Models\MarketingSuppression;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommunicationService
{
    public function __construct(
        private EmailProviderInterface $email,
        private SmsProviderInterface $sms,
        private WhatsAppProviderInterface $whatsapp,
        private ContactService $contacts,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function compose(int $organizationId, int $userId, array $data): CommunicationMessage
    {
        return CommunicationMessage::create([
            'organization_id' => $organizationId,
            'created_by_user_id' => $userId,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channel' => $data['channel'],
            'status' => 'draft',
            'is_bulk' => (bool) ($data['is_bulk'] ?? false),
            'is_newsletter' => (bool) ($data['is_newsletter'] ?? false),
            'mailing_list_id' => $data['mailing_list_id'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function updateDraft(CommunicationMessage $message, array $data): CommunicationMessage
    {
        abort_unless($message->status === 'draft', 422, 'Only draft messages can be updated.');

        $message->update([
            'subject' => $data['subject'] ?? $message->subject,
            'body' => $data['body'] ?? $message->body,
            'channel' => $data['channel'] ?? $message->channel,
            'is_bulk' => array_key_exists('is_bulk', $data) ? (bool) $data['is_bulk'] : $message->is_bulk,
            'is_newsletter' => array_key_exists('is_newsletter', $data) ? (bool) $data['is_newsletter'] : $message->is_newsletter,
            'mailing_list_id' => $data['mailing_list_id'] ?? $message->mailing_list_id,
        ]);

        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $message->recipients()->delete();
            $this->addRecipients($message, $data['recipients']);
        }

        return $message->fresh(['recipients']);
    }

    /** @return array{message: CommunicationMessage, sent: int, failed: int, total: int, saved_contact?: bool} */
    public function send(CommunicationMessage $message, ?array $contactToSave = null): array
    {
        return DB::transaction(function () use ($message, $contactToSave): array {
            $recipients = $message->recipients;
            if ($recipients->isEmpty() && $message->mailing_list_id !== null) {
                $this->attachMailingListRecipients($message);
                $recipients = $message->recipients()->get();
            }

            if (! $this->channelAvailable($message->channel)) {
                $message->update(['status' => 'failed']);

                return [
                    'message' => $message->fresh(['recipients']),
                    'sent' => 0,
                    'failed' => $recipients->count(),
                    'total' => $recipients->count(),
                ];
            }

            $sentCount = 0;
            $failedCount = 0;
            foreach ($recipients as $recipient) {
                if ($this->sendToRecipient($message, $recipient)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            $status = match (true) {
                $sentCount > 0 && $failedCount === 0 => 'sent',
                $sentCount > 0 && $failedCount > 0 => 'partially_sent',
                $recipients->isEmpty() => 'failed',
                default => 'failed',
            };

            $message->update([
                'status' => $status,
                'sent_at' => $sentCount > 0 ? now() : null,
            ]);

            $savedContact = false;
            if ($sentCount > 0 && $contactToSave !== null) {
                $saved = $this->contacts->saveAfterSuccess($message->organization_id, $contactToSave);
                $savedContact = $saved !== null;
            }

            return [
                'message' => $message->fresh(['recipients']),
                'sent' => $sentCount,
                'failed' => $failedCount,
                'total' => $recipients->count(),
                'saved_contact' => $savedContact,
            ];
        });
    }

    /** @param  list<array{email?: string, phone?: string, user_id?: int, name?: string}>  $recipients */
    public function addRecipients(CommunicationMessage $message, array $recipients): void
    {
        foreach ($this->dedupeRecipients($recipients) as $row) {
            CommunicationRecipient::create([
                'communication_message_id' => $message->id,
                'user_id' => $row['user_id'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{email?: string, phone?: string, user_id?: int, name?: string}>
     */
    public function resolveRecipients(int $organizationId, array $data): array
    {
        $mode = $data['recipient_mode'] ?? 'individual';
        $channel = $data['channel'] ?? 'email';

        $recipients = match ($mode) {
            'mailing_list', 'bulk' => $this->mailingListRecipients((int) ($data['mailing_list_id'] ?? 0)),
            'users' => $this->userRecipients($organizationId, $data['user_ids'] ?? []),
            'all_eligible' => $this->allEligibleRecipients($organizationId, $channel),
            'multiple' => $data['recipients'] ?? [],
            default => $data['recipients'] ?? [],
        };

        return $this->dedupeRecipients($recipients);
    }

    /** @return array<string, array<string, mixed>> */
    public function availableProviders(): array
    {
        $available = [];

        foreach (['email', 'sms', 'whatsapp'] as $channel) {
            if ($this->channelAvailable($channel)) {
                $available[$channel] = [
                    'connected' => true,
                    'driver' => $this->channelDriver($channel),
                ];
            }
        }

        return $available;
    }

    public function channelAvailable(string $channel): bool
    {
        $driver = $this->channelDriver($channel);

        return $driver !== '' && $driver !== 'none';
    }

    public function createMailingList(int $organizationId, string $name, ?string $description = null): MailingList
    {
        return MailingList::create([
            'organization_id' => $organizationId,
            'name' => $name,
            'description' => $description,
        ]);
    }

    /** @param  list<array{email?: string, phone?: string, user_id?: int}>  $members */
    public function addMailingListMembers(MailingList $list, array $members): void
    {
        foreach ($members as $member) {
            $normalizedEmail = $this->contacts->normalizeEmail($member['email'] ?? null);
            MailingListMember::firstOrCreate(
                [
                    'mailing_list_id' => $list->id,
                    'email' => $member['email'] ?? null,
                ],
                [
                    'user_id' => $member['user_id'] ?? null,
                    'phone' => $member['phone'] ?? null,
                ],
            );
            unset($normalizedEmail);
        }
    }

    /** @return list<array{email?: string, phone?: string, user_id?: int}> */
    private function mailingListRecipients(int $mailingListId): array
    {
        if ($mailingListId <= 0) {
            return [];
        }

        return MailingListMember::where('mailing_list_id', $mailingListId)
            ->get()
            ->map(fn (MailingListMember $m) => [
                'user_id' => $m->user_id,
                'email' => $m->email,
                'phone' => $m->phone,
            ])
            ->all();
    }

    /** @param  list<int>  $userIds */
    private function userRecipients(int $organizationId, array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->whereHas('organizations', fn ($q) => $q->whereKey($organizationId))
            ->get()
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'email' => $u->email,
                'phone' => $this->userPhone($u),
            ])
            ->all();
    }

    /** @return list<array{email?: string, phone?: string, user_id?: int}> */
    private function allEligibleRecipients(int $organizationId, string $channel): array
    {
        return User::query()
            ->whereHas('organizations', fn ($q) => $q->whereKey($organizationId))
            ->get()
            ->filter(function (User $user) use ($channel): bool {
                return match ($channel) {
                    'email' => filled($user->email),
                    'sms', 'whatsapp' => filled($this->userPhone($user)),
                    default => false,
                };
            })
            ->map(fn (User $u) => [
                'user_id' => $u->id,
                'email' => $u->email,
                'phone' => $this->userPhone($u),
            ])
            ->values()
            ->all();
    }

    private function attachMailingListRecipients(CommunicationMessage $message): void
    {
        $members = MailingListMember::where('mailing_list_id', $message->mailing_list_id)->get();
        foreach ($members as $member) {
            CommunicationRecipient::create([
                'communication_message_id' => $message->id,
                'user_id' => $member->user_id,
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => 'pending',
            ]);
        }
    }

    private function sendToRecipient(CommunicationMessage $message, CommunicationRecipient $recipient): bool
    {
        $channel = $message->channel;

        if (! $this->channelAvailable($channel)) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => 'Channel is not connected.',
            ]);

            return false;
        }

        if ($this->isSuppressed($message->organization_id, $channel, $recipient)) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => 'المستلم في قائمة الحظر.',
            ]);

            return false;
        }

        $result = match ($channel) {
            'email' => $this->email->send(
                (string) ($recipient->email ?? ''),
                $message->subject,
                $message->body,
            ),
            'sms' => $this->sms->send(
                (string) ($recipient->phone ?? ''),
                $message->body,
            ),
            'whatsapp' => $this->whatsapp->send(
                (string) ($recipient->phone ?? ''),
                $message->body,
            ),
            default => null,
        };

        if ($result === null || ! $result->success) {
            $recipient->update([
                'status' => 'failed',
                'provider' => $result?->provider ?? 'none',
                'error_message' => $result?->errorMessage ?? 'Channel is not connected.',
            ]);

            return false;
        }

        $recipient->update([
            'status' => 'sent',
            'provider' => $result->provider,
            'provider_message_id' => $result->providerMessageId,
            'sent_at' => now(),
        ]);

        return true;
    }

    private function isSuppressed(int $organizationId, string $channel, CommunicationRecipient $recipient): bool
    {
        $identifiers = array_filter([
            $recipient->email,
            $recipient->phone,
            $recipient->user_id !== null ? (string) $recipient->user_id : null,
        ]);

        if ($identifiers === []) {
            return false;
        }

        return MarketingSuppression::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->whereIn('identifier', $identifiers)
            ->exists();
    }

    /**
     * @param  list<array{email?: string, phone?: string, user_id?: int, name?: string}>  $recipients
     * @return list<array{email?: string, phone?: string, user_id?: int, name?: string}>
     */
    private function dedupeRecipients(array $recipients): array
    {
        $seen = [];
        $unique = [];

        foreach ($recipients as $row) {
            $email = $this->contacts->normalizeEmail($row['email'] ?? null);
            $phone = $this->contacts->normalizePhone($row['phone'] ?? null);
            $userId = $row['user_id'] ?? null;
            $key = $email ?? $phone ?? ($userId !== null ? "user:{$userId}" : null);

            if ($key === null || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $row;
        }

        return $unique;
    }

    private function channelDriver(string $channel): string
    {
        return strtolower((string) config("marketing.providers.{$channel}", 'mock'));
    }

    private function userPhone(User $user): ?string
    {
        return $user->identities()
            ->where('provider', \App\Models\UserIdentity::PROVIDER_PHONE)
            ->value('phone');
    }
}
