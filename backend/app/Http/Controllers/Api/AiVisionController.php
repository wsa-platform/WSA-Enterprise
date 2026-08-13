<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ScopesOwnedServices;
use App\Http\Controllers\Controller;
use App\Models\AiVisionUpload;
use App\Services\Ai\AiVisionUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiVisionController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ScopesOwnedServices;

    public function __construct(private AiVisionUploadService $uploadService) {}

    public function upload(Request $request): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.vision']);
        $data = $request->validate(['file' => ['required', 'file', 'max:5120']]);

        $upload = $this->uploadService->store(
            $this->organization($request),
            $request->user(),
            $data['file'],
        );

        return response()->json([
            'id' => $upload->id,
            'storage_path' => $upload->storage_path,
            'mime_type' => $upload->mime_type,
            'size_bytes' => $upload->size_bytes,
        ], 201);
    }

    public function show(Request $request, AiVisionUpload $upload): JsonResponse
    {
        $this->authorizeAnyPermission($request, ['ai.use', 'ai.vision']);
        abort_unless($upload->organization_id === $this->organization($request), 404);
        $this->ownership()->assertOwnedByUser($request->user(), $upload, $this->organization($request));

        return response()->json($upload->only(['id', 'mime_type', 'size_bytes', 'metadata', 'created_at']));
    }
}
