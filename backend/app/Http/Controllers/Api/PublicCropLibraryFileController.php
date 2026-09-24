<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\BindsPublicTenant;
use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Services\Media\LibraryFilePolicy;
use App\Services\Media\MediaReferenceService;
use App\Services\Tenancy\PublicTenantResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicCropLibraryFileController extends Controller
{
    use BindsPublicTenant;

    private const SECTIONS = [
        'farming-needs',
        'scientific-research',
        'industries',
        'other',
    ];

    public function __construct(
        private MediaReferenceService $media,
        private LibraryFilePolicy $filePolicy,
    ) {}

    public function index(Request $request): JsonResponse
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

        $validated = $request->validate([
            'organization' => ['nullable', 'string', 'max:255'],
            'organization_id' => ['nullable', 'integer'],
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
            ->where(function ($builder): void {
                $builder->whereNull('item_type')
                    ->orWhere('item_type', '!=', \App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH);
            })
            ->where(function ($builder): void {
                $builder->whereNull('metadata->library_file_section')
                    ->orWhere('metadata->library_file_section', '!=', 'scientific-research');
            })
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

    public function content(Request $request, int $fileId): StreamedResponse|JsonResponse
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

        $item = LibraryItem::query()
            ->where('organization_id', $organization->id)
            ->where('publication_status', 'published')
            ->where(function ($builder): void {
                $builder->whereNull('item_type')
                    ->orWhere('item_type', '!=', \App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH);
            })
            ->where(function ($builder): void {
                $builder->whereNull('metadata->library_file_section')
                    ->orWhere('metadata->library_file_section', '!=', 'scientific-research');
            })
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->whereKey($fileId)
            ->firstOrFail();

        $disk = (string) ($item->file_disk ?: 'local');
        $path = $this->media->validateAndSanitize([
            'file_disk' => $disk,
            'file_path' => (string) $item->file_path,
        ])['file_path'];

        return $this->filePolicy->stream($disk, $path, basename($path));
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
            'preview_mode' => $this->filePolicy->isInlinePreviewable($extension) ? 'inline_browser' : 'download_only',
        ];
    }
}
