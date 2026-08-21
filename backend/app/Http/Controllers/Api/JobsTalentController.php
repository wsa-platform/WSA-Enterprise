<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobContactRequest;
use App\Models\JobTalentProfile;
use App\Services\Jobs\JobCvParseService;
use App\Services\Jobs\JobTalentProfileService;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JobsTalentController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobTalentProfileService $profileService,
        private JobCvParseService $cvParseService,
        private UserGlobalOwnershipAuthorizer $ownership,
    ) {}

    public function showMine(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = $this->ownership
            ->scopeOwnedByUser(JobTalentProfile::with('contact'), $request->user())
            ->first();

        if ($profile?->contact) {
            $profile->contact->makeVisible(['email', 'phone', 'whatsapp', 'other_channels']);
        }

        return response()->json($profile?->toOwnerArray() ?? []);
    }

    public function upsert(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.register');

        $data = $request->validate([
            'professional_name' => ['required', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:64'],
            'region' => ['nullable', 'string', 'max:128'],
            'city' => ['nullable', 'string', 'max:128'],
            'skills' => ['nullable', 'array'],
            'experience' => ['nullable', 'array'],
            'education' => ['nullable', 'array'],
            'certificates' => ['nullable', 'array'],
            'languages' => ['nullable', 'array'],
            'disciplines' => ['nullable', 'array'],
            'work_preferences' => ['nullable', 'array'],
            'availability' => ['nullable', 'array'],
            'is_public' => ['nullable', 'boolean'],
            'contact' => ['nullable', 'array'],
            'contact.email' => ['nullable', 'email'],
            'contact.phone' => ['nullable', 'string', 'max:32'],
            'contact.whatsapp' => ['nullable', 'string', 'max:32'],
        ]);

        $profile = $this->profileService->registerOrUpdate(
            $request->user(),
            $data,
            $data['contact'] ?? null,
        );

        return response()->json($profile->load('contact')->toOwnerArray());
    }

    public function myContactRequests(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = $this->ownership
            ->scopeOwnedByUser(JobTalentProfile::query(), $request->user())
            ->firstOrFail();

        $requests = JobContactRequest::withoutGlobalScopes()
            ->where('talent_profile_id', $profile->id)
            ->latest()
            ->get()
            ->map(fn (JobContactRequest $contactRequest) => [
                'id' => $contactRequest->id,
                'status' => $contactRequest->status,
                'job_reference' => $contactRequest->job_reference,
                'created_at' => $contactRequest->created_at,
            ]);

        return response()->json(['data' => $requests]);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = $this->ownership
            ->scopeOwnedByUser(JobTalentProfile::query(), $request->user())
            ->firstOrFail();
        $request->validate(['cv' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:5120']]);

        $path = $request->file('cv')->store('job-cvs/'.$profile->id, 'local');
        $profile = $this->profileService->storeCv($profile, $path);

        return response()->json($profile->toOwnerArray());
    }

    public function downloadCv(Request $request): StreamedResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = $this->ownership
            ->scopeOwnedByUser(JobTalentProfile::query(), $request->user())
            ->firstOrFail();
        $path = $profile->storedCvDiskPath();
        abort_unless($path !== null, 404, 'CV not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    public function parseCv(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = $this->ownership
            ->scopeOwnedByUser(JobTalentProfile::query(), $request->user())
            ->firstOrFail();

        return response()->json(
            $this->cvParseService->parse($profile, $this->organization($request), $request->user()->id)
        );
    }
}
