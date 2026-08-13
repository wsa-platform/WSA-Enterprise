<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\MarketingAudienceSegment;
use App\Models\MarketingCampaign;
use App\Models\MarketingConsent;
use App\Models\MarketingDelivery;
use App\Models\MarketingSuppression;
use App\Models\MarketingTemplate;
use App\Services\Marketing\MarketingCampaignService;
use App\Services\Marketing\MarketingConsentService;
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketingController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private MarketingCampaignService $campaignService,
        private MarketingConsentService $consentService,
    ) {}

    private function ownership(): ServiceOwnershipAuthorizer
    {
        return app(ServiceOwnershipAuthorizer::class);
    }

    /** @param  Builder<Model>  $query */
    private function scopedOwnedQuery(Request $request, Builder $query): Builder
    {
        return $this->ownership()->scopeAccessibleServices(
            $query->where('organization_id', $this->organization($request)),
            $request->user(),
            $this->organization($request),
        );
    }

    private function assertOwnedCampaign(Request $request, MarketingCampaign $campaign, string $permission): void
    {
        abort_unless($campaign->organization_id === $this->organization($request), 404);
        $this->ownership()->authorizeManage(
            $request->user(),
            $campaign,
            $this->organization($request),
            $permission,
        );
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.admin');
        $orgId = $this->organization($request);

        return response()->json([
            'campaigns' => MarketingCampaign::where('organization_id', $orgId)->count(),
            'scheduled' => MarketingCampaign::where('organization_id', $orgId)->where('status', 'scheduled')->count(),
            'deliveries' => MarketingDelivery::where('organization_id', $orgId)->count(),
            'failed_deliveries' => MarketingDelivery::where('organization_id', $orgId)->whereIn('status', ['failed', 'rejected'])->count(),
            'segments' => MarketingAudienceSegment::where('organization_id', $orgId)->count(),
            'templates' => MarketingTemplate::where('organization_id', $orgId)->count(),
            'suppressions' => MarketingSuppression::where('organization_id', $orgId)->count(),
            'channel_stats' => MarketingDelivery::where('organization_id', $orgId)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->pluck('total', 'channel'),
        ]);
    }

    public function campaigns(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.view');

        return response()->json(
            $this->scopedOwnedQuery($request, MarketingCampaign::query())
                ->with(['segment', 'template'])
                ->latest()
                ->paginate(20)
        );
    }

    public function storeCampaign(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'audience_segment_id' => ['nullable', 'integer'],
            'template_id' => ['nullable', 'integer'],
            'content' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
        OrganizationScopeValidator::assert($this->organization($request), $data, [
            'audience_segment_id' => MarketingAudienceSegment::class,
            'template_id' => MarketingTemplate::class,
        ]);

        $campaign = $this->campaignService->create(
            $this->organization($request),
            $request->user(),
            $data,
        );

        return response()->json($campaign->load(['segment', 'template']), 201);
    }

    public function showCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.view');
        $this->assertOwnedCampaign($request, $campaign, 'marketing.view');

        return response()->json($campaign->load(['segment', 'template', 'deliveries']));
    }

    public function updateCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');

        if ($campaign->status !== 'draft') {
            abort(422, 'Only draft campaigns can be updated.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'channel' => ['sometimes', 'string', 'in:sms,email,whatsapp'],
            'audience_segment_id' => ['nullable', 'integer'],
            'template_id' => ['nullable', 'integer'],
            'content' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date'],
        ]);
        OrganizationScopeValidator::assert($this->organization($request), $data, [
            'audience_segment_id' => MarketingAudienceSegment::class,
            'template_id' => MarketingTemplate::class,
        ]);

        $campaign->update($this->ownership()->stripOwnerKeys($data));

        return response()->json($campaign->fresh()->load(['segment', 'template']));
    }

    public function destroyCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');
        abort_unless($campaign->status === 'draft', 422, 'Only draft campaigns can be deleted.');

        $campaign->delete();

        return response()->json(status: 204);
    }

    public function scheduleCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');

        return response()->json($this->campaignService->schedule($campaign, $request));
    }

    public function cancelCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');

        return response()->json($this->campaignService->cancel($campaign, $request));
    }

    public function previewCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.view');
        $locale = $request->query('locale', 'en');

        return response()->json($this->campaignService->preview($campaign, $locale));
    }

    public function testSendCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');
        $locale = $request->input('locale', 'en');

        return response()->json(
            $this->campaignService->testSend($campaign, $locale, $request),
            201,
        );
    }

    public function processCampaign(Request $request, MarketingCampaign $campaign): JsonResponse
    {
        $this->assertOwnedCampaign($request, $campaign, 'marketing.manage');

        return response()->json($this->campaignService->process($campaign, $request));
    }

    public function segments(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.view');

        return response()->json(
            $this->scopedOwnedQuery($request, MarketingAudienceSegment::query())->paginate(20)
        );
    }

    public function storeSegment(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.manage');
        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'criteria' => ['nullable', 'array'],
        ]);

        $segment = MarketingAudienceSegment::unguarded(fn () => MarketingAudienceSegment::create([
            ...$this->ownership()->assignOwnerFromSession($data, $request->user()),
            'organization_id' => $this->organization($request),
            'created_by_user_id' => $request->user()->id,
        ]));

        return response()->json($segment, 201);
    }

    public function templates(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.view');

        return response()->json(
            $this->scopedOwnedQuery($request, MarketingTemplate::query())->paginate(20)
        );
    }

    public function storeTemplate(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.manage');
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'name' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'translations' => ['required', 'array'],
        ]);

        $template = MarketingTemplate::unguarded(fn () => MarketingTemplate::create([
            ...$this->ownership()->assignOwnerFromSession($data, $request->user()),
            'organization_id' => $this->organization($request),
            'created_by_user_id' => $request->user()->id,
        ]));

        return response()->json($template, 201);
    }

    public function consents(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['marketing.admin', 'marketing.manage']);

        return response()->json(
            MarketingConsent::where('organization_id', $this->organization($request))->paginate(30)
        );
    }

    public function storeConsent(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['marketing.manage', 'marketing.admin']);
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'user_id' => ['nullable', 'integer'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'opted_in' => ['required', 'boolean'],
            'source' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->consentService->recordConsent(
                $this->organization($request),
                $data,
                $request->user()->id,
                $request,
            ),
            201,
        );
    }

    public function suppressions(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.admin');

        return response()->json(
            MarketingSuppression::where('organization_id', $this->organization($request))->paginate(30)
        );
    }

    public function storeSuppression(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.admin');
        $data = $request->validate([
            'channel' => ['required', 'string', 'in:sms,email,whatsapp'],
            'identifier' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->consentService->suppress(
                $this->organization($request),
                $data['channel'],
                $data['identifier'],
                $data['reason'] ?? null,
                $request->user()->id,
                $request,
            ),
            201,
        );
    }

    public function deliveries(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'marketing.view');

        $query = $this->scopedOwnedQuery($request, MarketingDelivery::query())->latest();
        if ($campaignId = $request->query('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        return response()->json($query->paginate(30));
    }
}
