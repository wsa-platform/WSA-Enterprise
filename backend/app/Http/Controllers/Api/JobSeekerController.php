<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Services\Authorization\PermissionService;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use App\Services\Recruitment\JobSeekerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobSeekerController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobSeekerService $service,
        private UserGlobalOwnershipAuthorizer $ownership,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.view');
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(JobSeekerProfile::STATUSES)],
            'country' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $includeHrPrivate = $this->canViewPrivate($request);
        $paginator = $this->service->search($filters, $filters['per_page'] ?? 15, $includeHrPrivate);
        $organizationId = $this->organization($request);

        return response()->json([
            'data' => collect($paginator->items())->map(
                fn (JobSeekerProfile $p) => $this->crmPayload($p, $includeHrPrivate, $organizationId)
            )->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function show(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.view');
        $includeHrPrivate = $this->canViewPrivate($request);

        return response()->json($this->crmPayload($jobSeeker, $includeHrPrivate, $this->organization($request)));
    }

    public function downloadCv(Request $request, JobSeekerProfile $jobSeeker): StreamedResponse
    {
        $this->authorizePermission($request, 'jobs.view');
        abort_unless($this->canViewPrivate($request), 403, 'CV access requires recruiter private-data permission.');
        abort_unless(
            $this->service->organizationHasVerifiedCandidateAccess($this->organization($request), $jobSeeker),
            403,
            'CV access requires a verified organization-scoped contact unlock.',
        );
        $path = $jobSeeker->storedCvDiskPath();
        abort_unless($path !== null, 404, 'CV not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    public function update(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.manage');
        $data = $this->ownership->stripOwnerKeys($request->validate(array_merge([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'desired_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'availability_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ], JobSeekerProfile::nestedPayloadRules())));
        unset($data['recruitment_status'], $data['user_id'], $data['cv_path'], $data['photo_path'], $data['completeness_percent']);
        $data = JobSeekerProfile::sanitizeNested($data);
        $data = array_intersect_key($data, array_flip([
            'full_name',
            'specialization',
            'biography',
            'country',
            'region',
            'city',
            'desired_salary',
            'salary_currency',
            'availability_date',
            'is_active',
            'skills',
            'experience',
            'education',
            'certifications',
            'languages',
            'email',
            'phone',
        ]));

        if (
            $this->canViewPrivate($request)
            && $this->service->organizationHasVerifiedCandidateAccess($this->organization($request), $jobSeeker)
        ) {
            $data = array_merge($data, $request->validate([
                'email' => ['nullable', 'email'],
                'phone' => ['nullable', 'string', 'max:50'],
            ]));
        }

        $jobSeeker->update($data);

        return response()->json($this->crmPayload(
            $jobSeeker->fresh(),
            $this->canViewPrivate($request),
            $this->organization($request),
        ));
    }

    public function updateStatus(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.status');
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(JobSeekerProfile::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $this->service->updateStatus(
            $jobSeeker,
            $data['status'],
            $request->user(),
            $data['notes'] ?? null,
            $this->organization($request),
            $request,
        );

        return response()->json($this->crmPayload(
            $profile,
            $this->canViewPrivate($request),
            $this->organization($request),
        ));
    }

    public function notes(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.notes');

        $notes = $jobSeeker->recruiterNotes()
            ->with('author:id,name')
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 25), 1), 100));

        return response()->json([
            'data' => $notes->items(),
            'current_page' => $notes->currentPage(),
            'last_page' => $notes->lastPage(),
            'total' => $notes->total(),
        ]);
    }

    public function storeNote(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.notes');
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $note = $this->service->addNote(
            $jobSeeker,
            $request->user(),
            $data['body'],
            $data['is_private'] ?? true,
            $this->organization($request),
            $request,
        );

        return response()->json($note, 201);
    }

    public function history(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.view');

        $includeNotes = app(PermissionService::class)->userCan(
            $request->user(),
            $this->organization($request),
            'jobs.notes',
        );

        $history = $jobSeeker->statusHistory()
            ->with('changedBy:id,name')
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 25), 1), 100));

        return response()->json([
            'data' => collect($history->items())->map(function ($row) use ($includeNotes) {
                $item = $row->toArray();
                if (! $includeNotes) {
                    unset($item['notes']);
                }

                return $item;
            })->values(),
            'current_page' => $history->currentPage(),
            'last_page' => $history->lastPage(),
            'total' => $history->total(),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'reports.recruitment');
        $days = min(max((int) $request->query('days', 30), 1), 365);

        return response()->json($this->service->report($days));
    }

    /** @return array<string, mixed> */
    private function crmPayload(JobSeekerProfile $profile, bool $includeHrPrivate, int $organizationId): array
    {
        $includeContact = $includeHrPrivate
            && $this->service->organizationHasVerifiedCandidateAccess($organizationId, $profile);

        return array_merge(
            $profile->toAdminArray($includeHrPrivate, $includeContact),
            ['user' => $profile->user?->only(['id', 'name'])],
        );
    }

    private function canViewPrivate(Request $request): bool
    {
        return app(PermissionService::class)->userCan(
            $request->user(),
            $this->organization($request),
            'jobs.private_data',
        );
    }
}
