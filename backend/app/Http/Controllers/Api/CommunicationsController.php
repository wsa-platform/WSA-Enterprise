<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\CommunicationMessage;
use App\Models\Contact;
use App\Models\MailingList;
use App\Models\MailingListMember;
use App\Models\MarketingCampaign;
use App\Models\MarketingDelivery;
use App\Services\Communications\CommunicationService;
use App\Services\Communications\ContactService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommunicationsController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function __construct(
        private CommunicationService $communications,
        private ContactService $contacts,
        private ServiceOwnershipAuthorizer $ownership,
    ) {}

    public function inbox(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $user = $request->user();
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);
        $sourceFilter = $request->query('source');

        $notifications = AppNotification::where('organization_id', $organizationId)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $user->id))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'source' => 'notification',
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        $messages = $this->scopedMessages($request)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (CommunicationMessage $m) => [
                'id' => $m->id,
                'source' => 'message',
                'type' => 'communication.message',
                'title' => $m->subject,
                'body' => sprintf('قناة: %s — حالة: %s', $m->channel, $m->status),
                'read_at' => null,
                'created_at' => $m->created_at,
            ]);

        $deliveriesQuery = MarketingDelivery::where('organization_id', $organizationId)
            ->with('campaign:id,name,channel,status');
        $deliveriesQuery = $this->ownership->scopeAccessibleServices($deliveriesQuery, $user, $organizationId);
        $deliveries = $deliveriesQuery
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (MarketingDelivery $d) => [
                'id' => $d->id,
                'source' => 'campaign_delivery',
                'type' => 'marketing.delivery',
                'title' => $d->campaign?->name ?? 'حملة تسويقية',
                'body' => sprintf('قناة: %s — حالة: %s', $d->channel, $d->status),
                'read_at' => null,
                'created_at' => $d->created_at,
            ]);

        $campaignsQuery = MarketingCampaign::where('organization_id', $organizationId);
        $campaignsQuery = $this->ownership->scopeAccessibleServices($campaignsQuery, $user, $organizationId);
        $campaigns = $campaignsQuery
            ->latest()
            ->limit(20)
            ->get(['id', 'name', 'channel', 'status', 'scheduled_at', 'created_at'])
            ->map(fn (MarketingCampaign $c) => [
                'id' => $c->id,
                'source' => 'campaign',
                'type' => 'marketing.campaign',
                'title' => $c->name,
                'body' => sprintf('قناة: %s — حالة: %s', $c->channel, $c->status),
                'read_at' => null,
                'created_at' => $c->created_at,
            ]);

        $items = $notifications->concat($messages)->concat($deliveries)->concat($campaigns);

        if ($sourceFilter) {
            $items = $items->where('source', $sourceFilter);
        }

        $sorted = $items->sortByDesc('created_at')->values();
        $total = $sorted->count();
        $data = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'notifications_count' => $notifications->count(),
                'messages_count' => $messages->count(),
                'deliveries_count' => $deliveries->count(),
                'campaigns_count' => $campaigns->count(),
                'unread_notifications' => $notifications->whereNull('read_at')->count(),
                'providers' => $this->communications->availableProviders(),
            ],
        ]);
    }

    public function messages(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        $query = $this->scopedMessages($request)
            ->withCount([
                'recipients',
                'recipients as sent_count' => fn ($q) => $q->where('status', 'sent'),
                'recipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
                'recipients as pending_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return $this->paginateQuery($request, $query);
    }

    public function drafts(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        $query = $this->scopedMessages($request)
            ->where('status', 'draft')
            ->withCount([
                'recipients',
                'recipients as sent_count' => fn ($q) => $q->where('status', 'sent'),
                'recipients as failed_count' => fn ($q) => $q->where('status', 'failed'),
                'recipients as pending_count' => fn ($q) => $q->where('status', 'pending'),
            ])
            ->latest();

        return $this->paginateQuery($request, $query);
    }

    public function showMessage(Request $request, CommunicationMessage $message): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $this->assertMessageAccessible($request, $message);

        return response()->json($message->load(['recipients', 'mailingList', 'creator:id,name,email']));
    }

    public function compose(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $data = $this->validatedComposeData($request);

        if (! empty($data['preview_only'])) {
            return response()->json($this->buildPreviewResponse($this->organization($request), $data));
        }

        $organizationId = $this->organization($request);
        $message = $this->communications->compose(
            $organizationId,
            $request->user()->id,
            $data,
        );

        $recipients = $this->communications->resolveRecipients($organizationId, $data);
        if ($recipients !== []) {
            $this->communications->addRecipients($message, $recipients);
        }

        return response()->json($message->load('recipients'), 201);
    }

    public function updateMessage(Request $request, CommunicationMessage $message): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $this->assertMessageAccessible($request, $message);

        $data = $this->validatedComposeData($request, draftOnly: true);
        $updated = $this->communications->updateDraft($message, $data);

        return response()->json($updated);
    }

    public function destroyMessage(Request $request, CommunicationMessage $message): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $this->assertMessageAccessible($request, $message);
        abort_unless($message->status === 'draft', 422, 'Only draft messages can be deleted.');

        $message->delete();

        return response()->json(['message' => 'Draft deleted.']);
    }

    public function send(Request $request, CommunicationMessage $message): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $this->assertMessageAccessible($request, $message);

        $data = $request->validate([
            'save_contact' => ['nullable', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $contactToSave = null;
        if (! empty($data['save_contact'])) {
            $contactToSave = [
                'name' => $data['contact_name'] ?? null,
                'email' => $data['contact_email'] ?? null,
                'phone' => $data['contact_phone'] ?? null,
            ];
        }

        $result = $this->communications->send($message, $contactToSave);

        return response()->json([
            ...$result['message']->toArray(),
            'delivery_stats' => [
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'total' => $result['total'],
            ],
            'saved_contact' => $result['saved_contact'] ?? false,
            'recipients' => $result['message']->recipients,
        ]);
    }

    public function contacts(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        return response()->json(
            $this->contacts->list(
                $this->organization($request),
                $request->query('search'),
                min(max((int) $request->query('per_page', 25), 1), 100),
            ),
        );
    }

    public function storeContact(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $contact = $this->contacts->create($this->organization($request), $data);

        return response()->json($contact, 201);
    }

    public function updateContact(Request $request, Contact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($contact->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer'],
        ]);

        return response()->json($this->contacts->update($contact, $data));
    }

    public function destroyContact(Request $request, Contact $contact): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($contact->organization_id === $this->organization($request), 404);

        $this->contacts->delete($contact);

        return response()->json(['message' => 'Contact deleted.']);
    }

    public function searchContacts(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $query = (string) $request->query('q', '');
        $limit = min(max((int) $request->query('limit', 10), 1), 25);

        $results = $this->contacts->search($this->organization($request), $query, $limit);

        return response()->json([
            'data' => $results->map(fn ($contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'last_contacted_at' => $contact->last_contacted_at,
            ]),
        ]);
    }

    public function mailingLists(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        return response()->json(
            MailingList::where('organization_id', $this->organization($request))
                ->withCount('members')
                ->latest()
                ->paginate(20),
        );
    }

    public function storeMailingList(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'members' => ['nullable', 'array'],
        ]);

        $list = $this->communications->createMailingList(
            $this->organization($request),
            $data['name'],
            $data['description'] ?? null,
        );

        if (! empty($data['members'])) {
            $this->communications->addMailingListMembers($list, $data['members']);
        }

        return response()->json($list->loadCount('members'), 201);
    }

    public function updateMailingList(Request $request, MailingList $mailingList): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($mailingList->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $mailingList->update($data);

        return response()->json($mailingList->loadCount('members'));
    }

    public function destroyMailingList(Request $request, MailingList $mailingList): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($mailingList->organization_id === $this->organization($request), 404);

        $mailingList->delete();

        return response()->json(['message' => 'Mailing list deleted.']);
    }

    public function mailingListMembers(Request $request, MailingList $mailingList): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($mailingList->organization_id === $this->organization($request), 404);

        return response()->json(
            $mailingList->members()->with('user:id,name,email')->paginate(50),
        );
    }

    public function addMailingListMembers(Request $request, MailingList $mailingList): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($mailingList->organization_id === $this->organization($request), 404);

        $data = $request->validate([
            'members' => ['required', 'array', 'min:1'],
            'members.*.email' => ['nullable', 'email'],
            'members.*.phone' => ['nullable', 'string', 'max:32'],
            'members.*.user_id' => ['nullable', 'integer'],
        ]);

        $this->communications->addMailingListMembers($mailingList, $data['members']);

        return response()->json($mailingList->loadCount('members'));
    }

    public function removeMailingListMember(Request $request, MailingList $mailingList, MailingListMember $member): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless(
            $mailingList->organization_id === $this->organization($request)
            && $member->mailing_list_id === $mailingList->id,
            404,
        );

        $member->delete();

        return response()->json(['message' => 'Member removed.']);
    }

    public function providers(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');

        return response()->json([
            'providers' => $this->communications->availableProviders(),
        ]);
    }

    /** @return Builder<CommunicationMessage> */
    private function scopedMessages(Request $request)
    {
        $organizationId = $this->organization($request);
        $query = CommunicationMessage::query()->where('organization_id', $organizationId);

        if (! $this->ownership->canSupervise($request->user(), $organizationId)) {
            $query->where('created_by_user_id', $request->user()->id);
        }

        return $query;
    }

    private function assertMessageAccessible(Request $request, CommunicationMessage $message): void
    {
        $organizationId = $this->organization($request);
        abort_unless((int) $message->organization_id === $organizationId, 404);

        if ($this->ownership->canSupervise($request->user(), $organizationId)) {
            return;
        }

        abort_unless(
            (int) $message->created_by_user_id === (int) $request->user()->id,
            403,
            'You can only access communications that you own.',
        );
    }

    /** @return array<string, mixed> */
    private function validatedComposeData(Request $request, bool $draftOnly = false): array
    {
        $rules = [
            'subject' => [$draftOnly ? 'sometimes' : 'required', 'string', 'max:255'],
            'body' => [$draftOnly ? 'sometimes' : 'required', 'string'],
            'channel' => [$draftOnly ? 'sometimes' : 'required', 'string', 'in:email,sms,whatsapp'],
            'is_bulk' => ['nullable', 'boolean'],
            'is_newsletter' => ['nullable', 'boolean'],
            'mailing_list_id' => ['nullable', 'integer'],
            'recipient_mode' => ['nullable', 'string', 'in:individual,multiple,users,mailing_list,bulk,all_eligible'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'recipients' => ['nullable', 'array'],
            'recipients.*.email' => ['nullable', 'email'],
            'recipients.*.phone' => ['nullable', 'string', 'max:32'],
            'recipients.*.user_id' => ['nullable', 'integer'],
            'recipients.*.name' => ['nullable', 'string', 'max:255'],
            'preview_only' => ['nullable', 'boolean'],
        ];

        $data = $request->validate($rules);

        if (! $draftOnly && empty($data['preview_only']) && isset($data['channel'])) {
            if (! $this->communications->channelAvailable($data['channel'])) {
                throw ValidationException::withMessages([
                    'channel' => ['قناة الإرسال غير متصلة أو غير مفعّلة.'],
                ]);
            }
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    private function buildPreviewResponse(int $organizationId, array $data): array
    {
        $recipients = $this->communications->resolveRecipients($organizationId, $data);
        $mailingListCount = null;

        if (($data['recipient_mode'] ?? '') === 'mailing_list' || ($data['recipient_mode'] ?? '') === 'bulk') {
            $listId = (int) ($data['mailing_list_id'] ?? 0);
            if ($listId > 0) {
                $mailingListCount = MailingList::where('organization_id', $organizationId)
                    ->whereKey($listId)
                    ->withCount('members')
                    ->first()?->members_count;
            }
        }

        return [
            'preview' => true,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channel' => $data['channel'],
            'recipient_count' => count($recipients),
            'mailing_list_member_count' => $mailingListCount,
            'recipients' => array_slice($recipients, 0, 10),
            'provider' => $this->communications->availableProviders()[$data['channel']] ?? null,
        ];
    }
}
