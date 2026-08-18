<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfile;
use App\Services\Ownership\UserGlobalOwnershipAuthorizer;
use App\Services\Recruitment\JobSeekerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'biography' => ['nullable', 'string'],
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'cv_path' => ['nullable', 'string', 'max:2048'],
            'desired_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'availability_date' => ['nullable', 'date'],
        ], JobSeekerProfile::nestedPayloadRules())));

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
}
