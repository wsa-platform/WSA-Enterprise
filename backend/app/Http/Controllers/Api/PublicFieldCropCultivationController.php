<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\Agriculture\FieldCropCultivationProfileService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicFieldCropCultivationController extends Controller
{
    public function __construct(
        private FieldCropCultivationProfileService $profileService,
    ) {}

    public function farmingNeedsProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'organization' => ['required_without:organization_id', 'string', 'max:255'],
            'organization_id' => ['required_without:organization', 'integer'],
            'selected_crop_id' => ['required', 'string', 'max:64'],
            'selected_crop_name' => ['required', 'string', 'max:255'],
            'selected_category_id' => ['nullable', 'string', 'max:64'],
            'selected_category_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $organization = $this->resolvePublicOrganization($validated);
        } catch (ModelNotFoundException) {
            return response()->json([
                'load_state' => 'organization_not_found',
                'message' => 'تعذر العثور على مؤسسة المنصة العامة. تحقق من إعدادات قاعدة البيانات أو معرّف المؤسسة.',
            ], 404);
        }

        $profile = $this->profileService->getProfile($organization->id, [
            'selected_crop_id' => $validated['selected_crop_id'],
            'selected_crop_name' => $validated['selected_crop_name'],
            'selected_category_id' => $validated['selected_category_id'] ?? '',
            'selected_category_name' => $validated['selected_category_name'] ?? '',
        ]);

        return response()->json($profile);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvePublicOrganization(array $validated): Organization
    {
        if (isset($validated['organization_id'])) {
            return Organization::query()->findOrFail($validated['organization_id']);
        }

        $slug = (string) ($validated['organization'] ?? config('wsa.public_organization_slug', 'wsa-demo'));

        $organization = Organization::query()->where('slug', $slug)->first();
        if ($organization !== null) {
            return $organization;
        }

        return Organization::query()->orderBy('id')->firstOrFail();
    }
}
