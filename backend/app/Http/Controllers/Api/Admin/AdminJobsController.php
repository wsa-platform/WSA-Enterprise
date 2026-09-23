<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesPlatformAdmin;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PlatformAdministratorAuthorizer;
use App\Services\Recruitment\JobSeekerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminJobsController extends Controller
{
    use AuthorizesPlatformAdmin;

    public function __construct(
        private JobSeekerService $jobSeekers,
        private AuditService $audit,
    ) {}

    public function seekers(Request $request): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.jobs.view');

        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(JobSeekerProfile::STATUSES)],
            'country' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->jobSeekers->search($filters, $filters['per_page'] ?? 15, true);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (JobSeekerProfile $profile) => [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'email' => $profile->email,
                'country' => $profile->country,
                'specialization' => $profile->specialization,
                'recruitment_status' => $profile->recruitment_status,
                'is_active' => $profile->is_active,
            ])->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function updateSeekerStatus(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.jobs.manage');

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(JobSeekerProfile::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $this->jobSeekers->updateStatus(
            $jobSeeker,
            $data['status'],
            $request->user(),
            $data['notes'] ?? null,
            null,
            $request,
        );

        $this->audit->record(
            action: 'admin.jobs.status',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $profile,
            newValues: [
                'job_seeker_id' => $profile->id,
                'recruitment_status' => $profile->recruitment_status,
            ],
            request: $request,
        );

        return response()->json([
            'id' => $profile->id,
            'recruitment_status' => $profile->recruitment_status,
        ]);
    }

    public function show(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.jobs.view');

        return response()->json($this->presentSeeker(
            $jobSeeker,
            app(PlatformAdministratorAuthorizer::class)
                ->hasPermission($request->user(), 'platform.jobs.manage'),
        ));
    }

    public function notes(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.jobs.view');

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
        $this->authorizePlatformAdmin($request, 'platform.jobs.manage');

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $note = $this->jobSeekers->addNote(
            $jobSeeker,
            $request->user(),
            $data['body'],
            $data['is_private'] ?? true,
            null,
            $request,
        );

        $this->audit->record(
            action: 'admin.jobs.note',
            organizationId: null,
            userId: $request->user()->id,
            auditable: $note,
            newValues: [
                'job_seeker_id' => $jobSeeker->id,
                'note_id' => $note->id,
                'is_private' => $note->is_private,
            ],
            request: $request,
        );

        return response()->json($note, 201);
    }

    public function history(Request $request, JobSeekerProfile $jobSeeker): JsonResponse
    {
        $this->authorizePlatformAdmin($request, 'platform.jobs.view');
        $includeNotes = app(PlatformAdministratorAuthorizer::class)
            ->hasPermission($request->user(), 'platform.jobs.manage');

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

    /** @return array<string, mixed> */
    private function presentSeeker(JobSeekerProfile $profile, bool $canManage): array
    {
        $data = $profile->toAdminArray($canManage, $canManage);
        if ($canManage) {
            $data['cv_path'] = $profile->cv_path;
        }

        return $data;
    }
}
