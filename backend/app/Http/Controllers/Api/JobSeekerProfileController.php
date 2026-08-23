<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Rules\FourPartFullName;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use App\Services\Recruitment\JobSeekerService;
use App\Services\Recruitment\RecruitmentRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobSeekerProfileController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobSeekerService $service,
        private UserGlobalOwnershipAuthorizer $ownership,
        private RecruitmentRoleService $recruitmentRoles,
    ) {}

    public function showMine(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->first();
        abort_unless($profile, 404, 'Job seeker profile not found.');

        return response()->json($profile->toOwnerArray());
    }

    public function upsertMine(Request $request): JsonResponse
    {
        $this->recruitmentRoles->assertCanAccessJobSeeker($request->user());
        $existing = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->first();
        $data = $this->ownership->stripOwnerKeys($request->validate(array_merge([
            'full_name' => ['required', 'string', 'max:255', new FourPartFullName],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:50'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'target_job_title' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'country' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'desired_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'availability_date' => ['nullable', 'date'],
            'date_of_birth' => ['required', 'date'],
            'nationality' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
        ], JobSeekerProfile::nestedPayloadRules())));
        $data = JobSeekerProfile::candidateWritable($data);
        $this->assertPrimaryQualificationComplete($data['education'] ?? null, $existing);

        $profile = $this->service->upsertForUser($request->user(), $data);

        return response()->json($profile->toOwnerArray(), $existing ? 200 : 201);
    }

    public function destroyMine(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $user)
            ->firstOrFail();
        $this->ownership->assertOwnedByUser($user, $profile);

        $profile->recruiterNotes()->delete();
        $profile->statusHistory()->delete();
        $profile->delete();

        return response()->json(['message' => __('jobs.application_deleted')]);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $request->validate(
            ['cv' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120']],
            [
                'cv.mimes' => __('jobs.cv_pdf_only'),
                'cv.mimetypes' => __('jobs.cv_pdf_only'),
            ],
        );

        $path = $request->file('cv')->store('job-cvs/'.$profile->id, 'local');
        if (is_string($profile->cv_path) && $profile->cv_path !== $path && Storage::disk('local')->exists($profile->cv_path)) {
            Storage::disk('local')->delete($profile->cv_path);
        }
        $profile->update(['cv_path' => $path]);

        return response()->json($profile->fresh()->toOwnerArray());
    }

    public function downloadCv(Request $request): StreamedResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $path = $profile->storedCvDiskPath();
        abort_unless($path !== null, 404, 'CV not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    public function uploadPrimaryQualification(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $request->validate(
            ['document' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120']],
            [
                'document.mimes' => __('jobs.primary_qualification_pdf_only'),
                'document.mimetypes' => __('jobs.primary_qualification_pdf_only'),
            ],
        );

        $path = $request->file('document')->store('job-qualifications/'.$profile->id, 'local');
        if (is_string($profile->primary_qualification_path) && Storage::disk('local')->exists($profile->primary_qualification_path)) {
            Storage::disk('local')->delete($profile->primary_qualification_path);
        }
        $profile->update(['primary_qualification_path' => $path]);

        return response()->json($profile->fresh()->toOwnerArray());
    }

    public function downloadPrimaryQualification(Request $request): StreamedResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $path = $profile->storedPrimaryQualificationDiskPath();
        abort_unless($path !== null, 404, 'Qualification document not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    /**
     * @param  mixed  $education
     */
    private function assertPrimaryQualificationComplete(mixed $education, ?JobSeekerProfile $existing): void
    {
        if (! is_array($education) || $education === []) {
            return;
        }

        $primary = $education[0] ?? [];
        $degree = is_array($primary) ? trim((string) ($primary['degree'] ?? '')) : '';
        if ($degree === '') {
            throw ValidationException::withMessages([
                'education.0.degree' => [__('jobs.primary_qualification_required')],
            ]);
        }

        if (! filled($existing?->primary_qualification_path)) {
            throw ValidationException::withMessages([
                'primary_qualification_document' => [__('jobs.primary_qualification_document_required')],
            ]);
        }
    }
}
