<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\OrganizationInvitation;
use App\Services\Audit\AuditService;
use App\Services\Billing\EntitlementService;
use App\Services\Organization\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function __construct(
        private InvitationService $invitations,
        private AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $query = OrganizationInvitation::query()
            ->where('organization_id', $organizationId)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest();

        return $this->paginateQuery($request, $query);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);
        $organization = $request->user()->organizations()->findOrFail($organizationId);

        app(EntitlementService::class)->assertUserCapacity($organizationId, $request->user()->id);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['sometimes', 'in:admin,member'],
        ]);

        $invitation = $this->invitations->create(
            $organization,
            $data['email'],
            $data['role'] ?? 'member',
            $request->user(),
        );

        $this->auditService->record(
            action: 'invitation.sent',
            organizationId: $organizationId,
            userId: $request->user()->id,
            auditable: $invitation,
            newValues: ['email' => $invitation->email, 'role' => $invitation->role],
            request: $request,
        );

        return response()->json([
            'id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'expires_at' => $invitation->expires_at,
            'token' => $invitation->token,
            'accept_path' => '/accept-invitation?token='.$invitation->token,
        ], 201);
    }

    public function destroy(Request $request, OrganizationInvitation $invitation): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        abort_unless($invitation->organization_id === $this->organization($request), 404);

        $this->invitations->revoke($invitation);

        $this->auditService->record(
            action: 'invitation.revoked',
            organizationId: $invitation->organization_id,
            userId: $request->user()->id,
            auditable: $invitation,
            request: $request,
        );

        return response()->json(status: 204);
    }

    public function accept(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $result = $this->invitations->accept(
            $data['token'],
            $data['name'] ?? null,
            $data['password'],
            $data['device_name'] ?? 'web',
        );

        $this->auditService->record(
            action: 'invitation.accepted',
            organizationId: $result['organization']->id,
            userId: $result['user']->id,
            auditable: $result['user'],
            newValues: ['email' => $result['user']->email],
            request: $request,
        );

        return response()->json([
            'token' => $result['token'],
            'user' => $result['user']->only(['id', 'name', 'email']),
            'organization' => $result['organization']->only(['id', 'name', 'slug']),
        ]);
    }
}
