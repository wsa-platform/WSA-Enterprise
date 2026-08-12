<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocaleFromHeader;
use App\Models\Organization;
use App\Services\Audit\AuditService;
use App\Services\Billing\OrganizationSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private OrganizationSettingsService $organizationSettingsService,
        private AuditService $auditService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        $organizationId = $this->organization($request);
        $organization = $request->user()->organizations()->findOrFail($organizationId);
        $membership = $organization->pivot;

        return response()->json([
            ...$organization->only(['id', 'name', 'slug']),
            'membership_role' => $membership->role ?? 'member',
            'is_active' => (bool) ($membership->is_active ?? true),
            'members_count' => $organization->members()->count(),
            'created_at' => $organization->created_at,
            'updated_at' => $organization->updated_at,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);
        $organization = Organization::findOrFail($organizationId);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:64',
                Rule::unique('organizations', 'slug')->ignore($organization->id),
            ],
        ]);

        if (isset($data['name']) && ! isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $oldValues = $organization->only(['name', 'slug']);
        $organization->update($data);

        $this->auditService->record(
            action: 'organization.updated',
            organizationId: $organizationId,
            userId: $request->user()->id,
            auditable: $organization,
            oldValues: $oldValues,
            newValues: $organization->only(['name', 'slug']),
            request: $request,
        );

        return response()->json($organization->only(['id', 'name', 'slug']));
    }

    public function settings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return response()->json(
            $this->organizationSettingsService->allForOrganization($this->organization($request))
        );
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $locale = $data['settings']['operations.locale'] ?? null;
        if ($locale !== null && ! in_array($locale, SetLocaleFromHeader::SUPPORTED_LOCALES, true)) {
            throw ValidationException::withMessages([
                'settings.operations.locale' => ['The selected locale is not supported.'],
            ]);
        }

        return response()->json(
            $this->organizationSettingsService->updateForOrganization(
                $organizationId,
                $data['settings'],
                $request->user()->id,
                $request,
            )
        );
    }
}
