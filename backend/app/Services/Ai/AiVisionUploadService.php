<?php

namespace App\Services\Ai;

use App\Models\AiVisionUpload;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiVisionUploadService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_BYTES = 5_242_880;

    public function __construct(private AuditService $auditService) {}

    public function store(int $organizationId, User $user, UploadedFile $file): AiVisionUpload
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['file' => ['Unsupported image type.']]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => ['Image exceeds maximum upload size.']]);
        }

        $path = $file->store('ai-vision/'.$organizationId, 'local');

        $upload = AiVisionUpload::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'owner_user_id' => $user->id,
            'storage_path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath() ?: ''),
            'metadata' => ['original_name' => $file->getClientOriginalName()],
        ]);

        $this->auditService->record(
            action: 'ai.vision_upload_stored',
            organizationId: $organizationId,
            userId: $user->id,
            auditable: $upload,
            newValues: ['upload_id' => $upload->id],
        );

        return $upload;
    }

    public function resolvePath(AiVisionUpload $upload, int $organizationId, int $userId): string
    {
        abort_unless($upload->organization_id === $organizationId && $upload->user_id === $userId, 404);

        if (! Storage::disk('local')->exists($upload->storage_path)) {
            throw ValidationException::withMessages(['upload' => ['Image file is unavailable.']]);
        }

        return Storage::disk('local')->path($upload->storage_path);
    }
}
