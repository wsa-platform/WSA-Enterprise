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
use App\Services\Ownership\ServiceOwnershipAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return response()->json([
            'id' => $contactRequest->id,
            'status' => $contactRequest->status,
            'job_reference' => $contactRequest->job_reference,
            'talent_profile_id' => $contactRequest->talent_profile_id,
        ], 201);
    }

    public function payContact(Request $request, int $contactRequestId): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $data = $request->validate(['idempotency_key' => ['required', 'string', 'max:64']]);

        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->findOrFail($contactRequestId);
        $this->assertContactRequestAccess($request, $contactRequest);

        $transaction = $this->exchangeService->initiatePayment(
            $contactRequest,
            $data['idempotency_key'],
            $request,
            $this->organization($request),
        );

        return response()->json([
            'transaction' => [
                'id' => $transaction->id,
                'contact_request_id' => $transaction->contact_request_id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'payment_status' => $transaction->payment_status,
                'contact_exchange_status' => $transaction->contact_exchange_status,
                'exchanged_at' => $transaction->exchanged_at,
            ],
            'exchange' => $this->exchangeService->exchangePayload($transaction),
            'hiring_record' => $transaction->employmentRecord?->toHiringArray(),
        ]);
    }

    public function unlockedContact(Request $request, int $contactRequestId): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->with(['transaction.employmentRecord'])
            ->findOrFail($contactRequestId);
        $this->assertContactRequestAccess($request, $contactRequest);

        $transaction = $this->exchangeService->assertUnlockedContact(
            $contactRequest,
            $contactRequest->transaction,
            $this->organization($request),
        );

        return response()->json($this->exchangeService->exchangePayload($transaction));
    }

    public function unlockedCv(Request $request, int $contactRequestId): StreamedResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->with(['transaction', 'talentProfile'])
            ->findOrFail($contactRequestId);
        $this->assertContactRequestAccess($request, $contactRequest);
        $this->exchangeService->assertUnlockedContact(
            $contactRequest,
            $contactRequest->transaction,
            $this->organization($request),
        );

        $talent = $contactRequest->talentProfile;
        $path = $talent?->storedCvDiskPath();
        abort_unless($path !== null, 404, 'CV not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    public function markHired(Request $request, int $contactRequestId): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $contactRequest = JobContactRequest::withoutGlobalScopes()
            ->where('organization_id', $this->organization($request))
            ->with('transaction')
            ->findOrFail($contactRequestId);
        $this->assertContactRequestAccess($request, $contactRequest);

        $record = $this->exchangeService->markHired(
            $contactRequest,
            $contactRequest->transaction,
            is_string($request->input('job_reference')) ? $request->input('job_reference') : null,
            $request,
            $this->organization($request),
        );

        return response()->json($record->toHiringArray());
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

    private function assertContactRequestAccess(Request $request, JobContactRequest $contactRequest): void
    {
        abort_unless($contactRequest->organization_id === $this->organization($request), 404);

        $user = $request->user();
        $organizationId = $this->organization($request);

        if (app(ServiceOwnershipAuthorizer::class)->canSupervise($user, $organizationId)) {
            return;
        }

        abort_unless(
            (int) $contactRequest->requested_by_user_id === $user->id,
            403,
            'You can only manage contact requests that you created.',
        );
    }
}
