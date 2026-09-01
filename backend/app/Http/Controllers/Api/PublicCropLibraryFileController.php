<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Models\Organization;
use App\Services\Media\MediaReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicCropLibraryFileController extends Controller
{
    private const SECTIONS = [
        'farming-needs',
        'scientific-research',
        'industries',
        'other',
    ];

    public function __construct(private MediaReferenceService $media) {}

    public function index(Request $request): JsonResponse
    {
        $organization = $this->resolvePublicOrganization($request);
        $validated = $request->validate([
            'plant_production_category_id' => ['required', 'string', 'max:64'],
            'field_crop_id' => ['required', 'string', 'max:64'],
            'library_file_section' => ['required', 'string', 'in:'.implode(',', self::SECTIONS)],
        ]);

        $categoryId = $validated['plant_production_category_id'];
        $cropId = $validated['field_crop_id'];
        $sectionId = $validated['library_file_section'];

        $items = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('publication_status', 'published')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->where(function ($query) use ($categoryId, $cropId, $sectionId): void {
                $query->where(function ($scoped) use ($categoryId, $cropId, $sectionId): void {
                    $scoped->where('metadata->plant_production_category_id', $categoryId)
                        ->where('metadata->field_crop_id', $cropId)
                        ->where('metadata->library_file_section', $sectionId);
                })->orWhere(function ($scoped) use ($categoryId, $cropId, $sectionId): void {
                    $scoped->where('metadata->field_crop_category_id', $categoryId)
                        ->where('metadata->field_crop_id', $cropId)
                        ->where('metadata->library_file_section', $sectionId);
                });
            })
            ->orderBy('title_ar')
            ->get(['id', 'title', 'title_ar', 'file_disk', 'file_path']);

        return response()->json([
            'organization_id' => $organization->id,
            'organization_slug' => $organization->slug,
            'data' => $items->map(fn (LibraryItem $item): array => $this->presentFile($item))->values(),
        ]);
    }

    public function content(Request $request, int $fileId): StreamedResponse
    {
        $organization = $this->resolvePublicOrganization($request);

        $item = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('publication_status', 'published')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->whereKey($fileId)
            ->firstOrFail();

        $disk = (string) ($item->file_disk ?: 'local');
        $path = $this->media->validateAndSanitize([
            'file_disk' => $disk,
            'file_path' => (string) $item->file_path,
        ])['file_path'];

        abort_unless(Storage::disk($disk)->exists($path), 404);

        $fileName = basename($path);
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $mimeType = $this->mimeTypeForExtension($extension);
        $disposition = $this->isInlinePreviewable($extension) ? 'inline' : 'attachment';

        return Storage::disk($disk)->response($path, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition.'; filename="'.$fileName.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function presentFile(LibraryItem $item): array
    {
        $fileName = basename((string) $item->file_path);
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));

        return [
            'id' => $item->id,
            'title' => $item->title,
            'title_ar' => $item->title_ar ?: $item->title,
            'extension' => $extension,
            'file_name' => $fileName,
            'preview_mode' => $this->isInlinePreviewable($extension) ? 'inline_browser' : 'download_only',
        ];
    }

    private function isInlinePreviewable(string $extension): bool
    {
        return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'txt', 'csv'], true);
    }

    private function mimeTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip' => 'application/zip',
            default => 'application/octet-stream',
        };
    }

    private function resolvePublicOrganization(Request $request): Organization
    {
        $data = $request->validate([
            'organization' => ['required_without:organization_id', 'string', 'max:255'],
            'organization_id' => ['required_without:organization', 'integer'],
        ]);

        if (isset($data['organization_id'])) {
            return Organization::query()->findOrFail($data['organization_id']);
        }

        return Organization::query()->where('slug', $data['organization'])->firstOrFail();
    }
}
