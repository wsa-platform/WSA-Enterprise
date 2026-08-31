<?php

namespace App\Services\Media;

use App\Models\MediaUpload;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaUploadService
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    private const MAX_BYTES = 5_242_880;

    public function __construct(private AuditService $auditService) {}

    public function store(
        int $organizationId,
        User $user,
        UploadedFile $file,
        string $context = 'general',
    ): MediaUpload {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['file' => ['Unsupported image type.']]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages(['file' => ['Image exceeds maximum upload size.']]);
        }

        $disk = 'public';
        $path = $file->store("media/{$organizationId}/{$context}", $disk);

        $upload = MediaUpload::create([
            'organization_id' => $organizationId,
            'user_id' => $user->id,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'context' => $context,
            'metadata' => ['original_name' => $file->getClientOriginalName()],
        ]);

        $this->auditService->record(
            action: 'media.uploaded',
            organizationId: $organizationId,
            userId: $user->id,
            auditable: $upload,
            newValues: ['upload_id' => $upload->id, 'context' => $context],
        );

        return $upload;
    }

    public function delete(MediaUpload $upload, int $organizationId): void
    {
        abort_unless($upload->organization_id === $organizationId, 404);

        if (Storage::disk($upload->storage_disk)->exists($upload->storage_path)) {
            Storage::disk($upload->storage_disk)->delete($upload->storage_path);
        }

        $upload->delete();
    }

    /** @return array<string, mixed> */
    public function toPublicArray(MediaUpload $upload, string $baseUrl): array
    {
        return [
            'id' => $upload->id,
            'url' => rtrim($baseUrl, '/').'/storage/'.ltrim($upload->storage_path, '/'),
            'mime_type' => $upload->mime_type,
            'size_bytes' => $upload->size_bytes,
            'context' => $upload->context,
            'original_name' => $upload->metadata['original_name'] ?? null,
            'created_at' => $upload->created_at,
        ];
    }
}
