<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\MediaUpload;
use App\Services\Audit\AuditService;
use App\Services\Media\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function __construct(
        private MediaUploadService $uploadService,
        private AuditService $auditService,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['library.manage', 'business.manage', 'platform.view']);

        $data = $request->validate([
            'file' => ['required', 'file'],
            'context' => ['sometimes', 'string', 'max:64'],
        ]);

        $organizationId = $this->organization($request);
        $upload = $this->uploadService->store(
            $organizationId,
            $request->user(),
            $data['file'],
            $data['context'] ?? 'general',
        );

        return response()->json(
            $this->uploadService->toPublicArray($upload, $request->getSchemeAndHttpHost()),
            201,
        );
    }

    public function show(Request $request, MediaUpload $mediaUpload): JsonResponse
    {
        $this->authorizePermission($request, 'platform.view');
        abort_unless($mediaUpload->organization_id === $this->organization($request), 404);

        return response()->json(
            $this->uploadService->toPublicArray($mediaUpload, $request->getSchemeAndHttpHost()),
        );
    }

    public function destroy(Request $request, MediaUpload $mediaUpload): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['library.manage', 'business.manage']);
        $organizationId = $this->organization($request);
        abort_unless($mediaUpload->organization_id === $organizationId, 404);

        $this->auditService->record(
            action: 'media.deleted',
            organizationId: $organizationId,
            userId: $request->user()->id,
            auditable: $mediaUpload,
            oldValues: ['upload_id' => $mediaUpload->id],
            request: $request,
        );

        $this->uploadService->delete($mediaUpload, $organizationId);

        return response()->json(['message' => 'Media deleted.']);
    }
}
