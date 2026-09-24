<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BindsPublicTenant;
use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\TrainingCourse;
use App\Services\Tenancy\PublicTenantResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPlatformController extends Controller
{
    use BindsPublicTenant;

    public function serviceCatalog(): JsonResponse
    {
        return response()->json([
            'platform' => 'WSA Enterprise',
            'description' => 'Integrated agricultural platform for farms, diagnosis, training, library, AI, beekeeping, and business operations.',
            'public_capabilities' => [
                'browse_service_catalog',
                'view_published_library_items',
                'view_published_training_courses',
                'register_service_owner_account',
                'login',
            ],
            'protected_capabilities' => [
                'create_and_manage_owned_services',
                'diagnosis_requests',
                'ai_assistant_and_vision',
                'business_operations',
                'billing_and_paid_features',
            ],
            'service_modules' => [
                ['key' => 'farm', 'label' => 'Farm management', 'requires_auth' => true],
                ['key' => 'crop', 'label' => 'Crop management', 'requires_auth' => true],
                ['key' => 'soil', 'label' => 'Soil analysis', 'requires_auth' => true],
                ['key' => 'diagnosis', 'label' => 'Plant diagnosis', 'requires_auth' => true],
                ['key' => 'training', 'label' => 'Training courses', 'requires_auth' => true],
                ['key' => 'library', 'label' => 'Knowledge library', 'requires_auth' => true],
                ['key' => 'ai', 'label' => 'AI services', 'requires_auth' => true],
                ['key' => 'beekeeping', 'label' => 'Beekeeping', 'requires_auth' => true],
                ['key' => 'business', 'label' => 'Business & commerce', 'requires_auth' => true],
                ['key' => 'jobs', 'label' => 'Jobs marketplace', 'requires_auth' => true],
            ],
            'browse_parameters' => [
                'organization' => 'Compatibility only — public tenant is server-resolved',
                'organization_id' => 'Compatibility only — public tenant is server-resolved',
            ],
        ]);
    }

    public function publishedLibraryItems(Request $request): JsonResponse
    {
        try {
            $organization = $this->bindPublicOrganization($request);
        } catch (PublicTenantResolutionException) {
            return response()->json([
                'status' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
        ]);

        // Library product contract: verified scientific research is authenticated-only.
        $query = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('publication_status', 'published')
            ->where(function ($builder): void {
                $builder->whereNull('item_type')
                    ->orWhere('item_type', '!=', \App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH);
            })
            ->select(['id', 'slug', 'title', 'title_ar', 'summary', 'summary_ar', 'item_type', 'locale', 'published_at'])
            ->latest('published_at');

        if ($locale = $request->query('locale')) {
            $query->where('locale', $locale);
        }

        return response()->json([
            'organization_id' => $organization->id,
            'organization_slug' => $organization->slug,
            ...$query->paginate(min((int) $request->query('per_page', 15), 50))->toArray(),
        ]);
    }

    public function publishedTrainingCourses(Request $request): JsonResponse
    {
        try {
            $organization = $this->bindPublicOrganization($request);
        } catch (PublicTenantResolutionException) {
            return response()->json([
                'status' => 'public_organization_unavailable',
                'message' => 'Public organization is unavailable.',
                'error' => [
                    'code' => 'public_organization_unavailable',
                    'http_status' => 503,
                    'message' => 'Public organization is unavailable.',
                    'details' => null,
                ],
            ], 503);
        }

        $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
        ]);

        $query = TrainingCourse::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'published')
            ->select(['id', 'code', 'title', 'title_ar', 'description', 'description_ar', 'locale'])
            ->orderBy('sort_order');

        if ($locale = $request->query('locale')) {
            $query->where('locale', $locale);
        }

        return response()->json([
            'organization_id' => $organization->id,
            'organization_slug' => $organization->slug,
            ...$query->paginate(min((int) $request->query('per_page', 15), 50))->toArray(),
        ]);
    }

}
