<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobTalentProfile;
use App\Services\Jobs\JobCvParseService;
use App\Services\Jobs\JobTalentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobsTalentController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private JobTalentProfileService $profileService,
        private JobCvParseService $cvParseService,
    ) {}

    public function showMine(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = JobTalentProfile::with('contact')->where('user_id', $request->user()->id)->first();

        return response()->json($profile);
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
            'employment_status' => ['nullable', 'string', 'max:32'],
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

        return response()->json($profile);
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = JobTalentProfile::where('user_id', $request->user()->id)->firstOrFail();
        $request->validate(['cv' => ['required', 'file', 'mimes:pdf,doc,docx,txt', 'max:5120']]);

        $path = $request->file('cv')->store('job-cvs/'.$profile->id, 'local');
        $profile = $this->profileService->storeCv($profile, $path);

        return response()->json($profile);
    }

    public function parseCv(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'jobs.talent.manage');
        $profile = JobTalentProfile::where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(
            $this->cvParseService->parse($profile, $this->organization($request), $request->user()->id)
        );
    }
}
