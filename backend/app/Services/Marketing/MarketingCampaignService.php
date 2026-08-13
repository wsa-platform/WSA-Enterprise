<?php

namespace App\Services\Marketing;

use App\Models\MarketingCampaign;
use App\Models\MarketingDelivery;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketingCampaignService
{
    public function __construct(
        private MarketingProviderResolver $providers,
        private MarketingConsentService $consentService,
        private MarketingAudienceResolver $audienceResolver,
        private AuditService $auditService,
    ) {}

    /** @param  array<string, mixed>  $data */
    public function create(int $organizationId, User $user, array $data): MarketingCampaign
    {
        return MarketingCampaign::create([
            ...app(\App\Services\Ownership\ServiceOwnershipAuthorizer::class)->assignOwnerFromSession($data, $user),
            'organization_id' => $organizationId,
            'created_by_user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function schedule(MarketingCampaign $campaign, ?Request $request = null): MarketingCampaign
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw ValidationException::withMessages(['status' => ['Campaign cannot be scheduled in its current state.']]);
        }

        $campaign->update(['status' => 'scheduled', 'scheduled_at' => $campaign->scheduled_at ?? now()]);
        $this->audit('marketing.campaign_scheduled', $campaign, $request);

        return $campaign->fresh();
    }

    public function cancel(MarketingCampaign $campaign, ?Request $request = null): MarketingCampaign
    {
        if (in_array($campaign->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => ['Campaign cannot be cancelled.']]);
        }

        $campaign->update(['status' => 'cancelled']);
        MarketingDelivery::where('campaign_id', $campaign->id)->where('status', 'queued')->update(['status' => 'cancelled']);
        $this->audit('marketing.campaign_cancelled', $campaign, $request);

        return $campaign->fresh();
    }

    /** @return array<string, mixed> */
    public function preview(MarketingCampaign $campaign, string $locale = 'en'): array
    {
        $template = $campaign->template?->translationFor($locale);
        $content = $campaign->content ?? [];

        return [
            'channel' => $campaign->channel,
            'locale' => $locale,
            'subject' => $content['subject'] ?? $template['subject'] ?? $campaign->name,
            'body' => $content['body'] ?? $template['body'] ?? $campaign->description,
        ];
    }

    public function testSend(MarketingCampaign $campaign, string $locale, ?Request $request = null): MarketingDelivery
    {
        $preview = $this->preview($campaign, $locale);
        $recipient = config('marketing.test_send_recipient', 'test@wsa.test');

        return $this->dispatchToRecipient($campaign, $recipient, null, $preview, true, $request);
    }

    public function process(MarketingCampaign $campaign, ?Request $request = null): MarketingCampaign
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw ValidationException::withMessages(['status' => ['Campaign cannot be processed in its current state.']]);
        }

        return DB::transaction(function () use ($campaign, $request): MarketingCampaign {
            $campaign->update(['status' => 'processing', 'started_at' => now()]);
            $this->audit('marketing.campaign_started', $campaign, $request);

            $recipients = $campaign->segment !== null
                ? $this->audienceResolver->resolve($campaign->segment)
                : collect([]);

            $sent = 0;
            $failed = 0;

            foreach ($recipients as $recipient) {
                if (! $this->consentService->isAllowed(
                    $campaign->organization_id,
                    $campaign->channel,
                    User::find($recipient['user_id']),
                    $recipient['email'],
                    $recipient['phone'],
                )) {
                    $failed++;
                    MarketingDelivery::create([
                        'organization_id' => $campaign->organization_id,
                        'owner_user_id' => $campaign->owner_user_id,
                        'campaign_id' => $campaign->id,
                        'recipient_type' => $recipient['type'],
                        'recipient_id' => $recipient['user_id'],
                        'channel' => $campaign->channel,
                        'status' => 'rejected',
                        'provider' => 'consent',
                        'error_code' => 'opt_out',
                        'error_message' => 'Recipient has not opted in or is suppressed.',
                        'failed_at' => now(),
                    ]);

                    continue;
                }

                $preview = $this->preview($campaign, 'en');
                $delivery = $this->dispatchToRecipient(
                    $campaign,
                    $recipient['email'],
                    $recipient['user_id'],
                    $preview,
                    false,
                    $request,
                );

                $delivery->status === 'delivered' ? $sent++ : $failed++;
            }

            $status = match (true) {
                $sent > 0 && $failed === 0 => 'completed',
                $sent > 0 && $failed > 0 => 'partially_failed',
                default => 'failed',
            };

            $campaign->update(['status' => $status, 'completed_at' => now()]);
            $this->audit('marketing.campaign_completed', $campaign, $request);

            return $campaign->fresh();
        });
    }

    /** @param  array<string, mixed>  $preview */
    private function dispatchToRecipient(
        MarketingCampaign $campaign,
        string $to,
        ?int $recipientId,
        array $preview,
        bool $isTest,
        ?Request $request,
    ): MarketingDelivery {
        $delivery = MarketingDelivery::create([
            'organization_id' => $campaign->organization_id,
            'owner_user_id' => $campaign->owner_user_id,
            'campaign_id' => $campaign->id,
            'recipient_type' => $isTest ? 'test' : 'user',
            'recipient_id' => $recipientId,
            'channel' => $campaign->channel,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        $result = match ($campaign->channel) {
            'sms' => $this->providers->sms()->send($to, (string) $preview['body']),
            'email' => $this->providers->email()->send($to, (string) $preview['subject'], (string) $preview['body']),
            'whatsapp' => $this->providers->whatsapp()->send($to, (string) $preview['body']),
            default => throw ValidationException::withMessages(['channel' => ['Unsupported channel.']]),
        };

        $delivery->update([
            'provider' => $result->provider,
            'provider_message_id' => $result->providerMessageId,
            'status' => $result->success ? 'delivered' : 'failed',
            'error_code' => $result->errorCode,
            'error_message' => $result->errorMessage,
            'sent_at' => now(),
            'delivered_at' => $result->success ? now() : null,
            'failed_at' => $result->success ? null : now(),
        ]);

        return $delivery->fresh();
    }

    private function audit(string $action, MarketingCampaign $campaign, ?Request $request): void
    {
        $this->auditService->record(
            action: $action,
            organizationId: $campaign->organization_id,
            userId: $campaign->created_by_user_id,
            auditable: $campaign,
            newValues: ['campaign_id' => $campaign->id, 'status' => $campaign->status],
            request: $request,
        );
    }
}
