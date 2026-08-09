<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Models\DiagnosisRequest;
use App\Models\Farm;
use App\Models\LibraryItem;
use App\Models\TrainingCourse;
use App\Models\TrainingEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformController extends Controller
{
    use ResolvesOrganization;

    public function organizations(Request $request): JsonResponse
    {
        $organizations = $request->user()
            ->organizations()
            ->select(['organizations.id', 'organizations.name', 'organizations.slug'])
            ->orderBy('organizations.name')
            ->get()
            ->map(fn ($organization) => [
                ...$organization->only(['id', 'name', 'slug']),
                'role' => $organization->pivot->role ?? null,
            ]);

        return response()->json($organizations);
    }

    public function workflowSummary(Request $request): JsonResponse
    {
        $organizationId = $this->organization($request);

        return response()->json([
            'organization_id' => $organizationId,
            'farms' => Farm::where('organization_id', $organizationId)->count(),
            'diagnosis_requests' => DiagnosisRequest::where('organization_id', $organizationId)->count(),
            'training_courses' => TrainingCourse::where('organization_id', $organizationId)->where('status', 'published')->count(),
            'library_items' => LibraryItem::where('organization_id', $organizationId)->where('publication_status', 'published')->count(),
            'active_enrollments' => TrainingEnrollment::where('organization_id', $organizationId)->where('status', 'active')->count(),
        ]);
    }
}
