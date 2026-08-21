<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use App\Services\Recruitment\JobSeekerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobSeekerProfileController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobSeekerService $service,
        private UserGlobalOwnershipAuthorizer $ownership,
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
        $data = $this->ownership->stripOwnerKeys($request->validate(array_merge([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'target_job_title' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'desired_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'availability_date' => ['nullable', 'date'],
            'date_of_birth' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'years_of_experience' => ['nullable', 'integer', 'min:0', 'max:80'],
        ], JobSeekerProfile::nestedPayloadRules())));
        $data = JobSeekerProfile::candidateWritable($data);

        $existing = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->first();
        $profile = $this->service->upsertForUser($request->user(), $data);

        return response()->json($profile->toOwnerArray(), $existing ? 200 : 201);
    }

    public function destroyMine(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $this->ownership->assertOwnedByUser($request->user(), $profile);
        $profile->update(['is_active' => false]);

        return response()->json(['message' => 'Profile deactivated.']);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $request->validate(['cv' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:5120']]);

        $path = $request->file('cv')->store('job-cvs/'.$profile->id, 'local');
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

    public function uploadPhoto(Request $request): JsonResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $path = $request->file('photo')->store('job-photos/'.$profile->id, 'local');
        if (is_string($profile->photo_path) && Storage::disk('local')->exists($profile->photo_path)) {
            Storage::disk('local')->delete($profile->photo_path);
        }
        $profile->update(['photo_path' => $path]);

        return response()->json($profile->fresh()->toOwnerArray());
    }

    public function downloadPhoto(Request $request): StreamedResponse
    {
        $profile = $this->ownership
            ->scopeOwnedByUser(JobSeekerProfile::query(), $request->user())
            ->firstOrFail();
        $path = $profile->storedPhotoDiskPath();
        abort_unless($path !== null, 404, 'Photo not found.');

        return Storage::disk('local')->download($path, basename($path));
    }
}
