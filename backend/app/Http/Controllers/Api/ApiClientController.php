<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Services\Api\ApiClientService;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiClientController extends Controller
{
    use AuthorizesOrganizationAccess;
    use PaginatesOrganizationRecords;

    public function __construct(
        private ApiClientService $apiClientService,
        private AuditService $auditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');

        return $this->paginateQuery(
            $request,
            ApiClient::query()->latest()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        $organizationId = $this->organization($request);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_clients', 'name')->where('organization_id', $organizationId),
            ],
            'scopes' => ['sometimes', 'array'],
            'scopes.*' => ['string', 'max:64'],
        ]);

        $result = $this->apiClientService->create(
            $organizationId,
            $data['name'],
            $request->user()->id,
            $data['scopes'] ?? [],
        );

        $client = $result['client'];

        $this->auditService->record(
            action: 'api_client.created',
            organizationId: $organizationId,
            userId: $request->user()->id,
            auditable: $client,
            newValues: [
                'name' => $client->name,
                'client_id' => $client->client_id,
            ],
            request: $request,
        );

        return response()->json([
            'client' => $client,
            'client_secret' => $result['client_secret'],
            'message' => 'Store the client secret securely. It will not be shown again.',
        ], 201);
    }

    public function revoke(Request $request, ApiClient $apiClient): JsonResponse
    {
        $this->authorizePermission($request, 'access.manage');
        abort_unless($apiClient->organization_id === $this->organization($request), 404);

        $revoked = $this->apiClientService->revoke($apiClient);

        $this->auditService->record(
            action: 'api_client.revoked',
            organizationId: $revoked->organization_id,
            userId: $request->user()->id,
            auditable: $revoked,
            request: $request,
        );

        return response()->json($revoked);
    }
}
