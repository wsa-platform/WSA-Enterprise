<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobContactRequest;
use App\Models\JobModerationReport;
use App\Models\JobTalentProfile;
use App\Services\Jobs\JobContactExchangeService;
use App\Services\Jobs\JobMatchingService;
use App\Services\Jobs\JobTalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobsEmployerController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobTalentProfileService $profileService,
        private JobContactExchangeService $exchangeService,
        private JobMatchingService $matchingService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.view');
        $filters = $request->validate([
            'country' => ['nullable', 'string'],
            'region' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'specialization' => ['nullable', 'string'],
            'discipline' => ['nullable', 'string'],
            'skill' => ['nullable', 'string'],
            'employment_status' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->profileService->searchPublic($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (JobTalentProfile $p) => $p->toPublicArray())->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(Request $request, JobTalentProfile $talentProfile): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.view');
        abort_unless($talentProfile->is_public, 404);

        return response()->json($talentProfile->toPublicArray());
    }

    public function match(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $data = $request->validate(['requirements' => ['required', 'array'], 'limit' => ['nullable', 'integer', 'min:1', 'max:25']]);

        return response()->json([
            'matches' => $this->matchingService->match(
                $this->organization($request),
                $request->user()->id,
                $data['requirements'],
                $data['limit'] ?? 10,
            ),
        ]);
    }

    public function requestContact(Request $request, JobTalentProfile $talentProfile): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $data = $request->validate([
            'employer_contact' => ['required', 'array'],
            'employer_contact.name' => ['required', 'string'],
            'employer_contact.email' => ['required', 'email'],
            'employer_contact.phone' => ['nullable', 'string'],
            'employer_contact.whatsapp' => ['nullable', 'string'],
            'job_reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $contactRequest = $this->exchangeService->createRequest(
            $this->organization($request),
            $request->user()->id,
            $talentProfile,
            $data['employer_contact'],
            $data['job_reference'] ?? null,
            $data['notes'] ?? null,
            $data['idempotency_key'] ?? null,
        );

        return response()->json($contactRequest, 201);
    }

    public function payContact(Request $request, int $contactRequestId): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:64']]);

        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->findOrFail($contactRequestId);

        $transaction = $this->exchangeService->initiatePayment($contactRequest, $data['idempotency_key'], $request);

        return response()->json([
            'transaction' => $transaction,
            'exchange' => $this->exchangeService->exchangePayload($transaction),
        ]);
    }

    public function markHired(Request $request, int $contactRequestId): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->with('transaction')
            ->findOrFail($contactRequestId);

        $record = $this->exchangeService->markHired(
            $contactRequest,
            $contactRequest->transaction,
            $request->input('job_reference'),
            $request,
        );

        return response()->json($record);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_type' => ['required', 'string'],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', 'string'],
        ]);

        $report = JobModerationReport::create([
            'reporter_user_id' => $request->user()->id,
            'organization_id' => $this->organization($request),
            'target_type' => $data['target_type'],
            'target_id' => $data['target_id'],
            'reason' => $data['reason'],
        ]);

        return response()->json($report, 201);
    }
}
