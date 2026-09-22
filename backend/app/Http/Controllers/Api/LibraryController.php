<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{CropType, LibraryCategory, LibraryItem, LibraryTag};
use App\Services\Media\MediaReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryController extends Controller
{
    use AuthorizesOrganizationAccess;
    use ManagesUserOwnedModules;
    use PaginatesOrganizationRecords;

    private const MODULES = [
        'categories' => [LibraryCategory::class, ['parent_id'=>['nullable','integer','exists:library_categories,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255']], ['parent_id'=>LibraryCategory::class]],
        'tags' => [LibraryTag::class, ['name'=>['required','string','max:64'], 'name_ar'=>['nullable','string','max:64']], []],
        'items' => [LibraryItem::class, ['category_id'=>['nullable','integer','exists:library_categories,id'], 'crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'slug'=>['required','string','max:128'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'summary'=>['nullable','string'], 'summary_ar'=>['nullable','string'], 'content'=>['nullable','string'], 'content_ar'=>['nullable','string'], 'item_type'=>['sometimes','string','max:32'], 'author'=>['nullable','string','max:255'], 'source'=>['nullable','string','max:255'], 'locale'=>['sometimes','string','max:8'], 'publication_status'=>['sometimes','string','max:32'], 'published_at'=>['nullable','date'], 'file_disk'=>['nullable','string','max:32'], 'file_path'=>['nullable','string','max:255'], 'metadata'=>['nullable','array'], 'tag_ids'=>['nullable','array'], 'tag_ids.*'=>['integer','exists:library_tags,id']], ['category_id'=>LibraryCategory::class, 'crop_type_id'=>CropType::class]],
    ];

    private const FILE_SECTIONS = [
        'farming-needs',
        'scientific-research',
        'industries',
        'other',
    ];

    public function __construct(private MediaReferenceService $media) {}

    protected function moduleManagePermission(Request $request, string $module): string
    {
        return 'library.manage';
    }

    protected function moduleViewPermission(Request $request, string $module): string
    {
        return 'library.view';
    }

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        $data = $this->ownership()->stripOwnerKeys($data);
        OrganizationScopeValidator::assert($this->organization($request), $data, $relations);

        if ($module === 'items') {
            $data = $this->media->validateAndSanitize($data);
        }

        return $data;
    }

    /**
     * Library Page read contract: authentication alone grants complete Library browse.
     * No organization / owner / supervisor / item_type content filters on read paths.
     */
    public function index(Request $request, string $module): JsonResponse
    {
        abort_unless($request->user() !== null, 401);

        [$class] = $this->config($module);
        $query = $class::query()->latest();

        if ($module === 'items') {
            if ($status = $request->query('publication_status')) {
                $query->where('publication_status', $status);
            }
            if ($categoryId = $request->query('category_id')) {
                $query->where('category_id', $categoryId);
            }
            if ($cropTypeId = $request->query('crop_type_id')) {
                $query->where('crop_type_id', $cropTypeId);
            }

            $query->with(['tags', 'category:id,name,name_ar', 'cropType:id,code,name']);
        }

        return $this->paginateQuery($request, $query);
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 401);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'crop_type_id' => ['nullable', 'integer'],
            'tag' => ['nullable', 'string', 'max:64'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $term = $validated['q'];
        $likeOperator = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = LibraryItem::query()
            ->where(function ($builder) use ($term, $likeOperator) {
                $builder->where('title', $likeOperator, "%{$term}%")
                    ->orWhere('title_ar', $likeOperator, "%{$term}%")
                    ->orWhere('summary', $likeOperator, "%{$term}%")
                    ->orWhere('summary_ar', $likeOperator, "%{$term}%")
                    ->orWhere('content', $likeOperator, "%{$term}%")
                    ->orWhere('content_ar', $likeOperator, "%{$term}%")
                    ->orWhere('slug', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->field_crop_name', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->scientific_name', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->field_crop_id', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->knowledge_option', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->service_option', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->field_crop_category_name', $likeOperator, "%{$term}%")
                    ->orWhere('metadata->topic_name', $likeOperator, "%{$term}%");
            })
            ->with(['tags', 'category:id,name,name_ar', 'cropType:id,code,name']);

        if ($categoryId = $validated['category_id'] ?? null) {
            $query->where('category_id', $categoryId);
        }

        if ($cropTypeId = $validated['crop_type_id'] ?? null) {
            $query->where('crop_type_id', $cropTypeId);
        }

        if ($tag = $validated['tag'] ?? $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tag)->orWhere('name_ar', $tag));
        }

        if ($likeOperator === 'ilike') {
            $query->orderByRaw('CASE WHEN title ILIKE ? OR title_ar ILIKE ? THEN 0 ELSE 1 END', ["{$term}", "{$term}"]);
        } else {
            $query->orderByRaw('CASE WHEN title LIKE ? OR title_ar LIKE ? THEN 0 ELSE 1 END', ["{$term}", "{$term}"]);
        }

        $query->orderByDesc('published_at');

        $paginator = $query->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            ...$paginator->toArray(),
            'query' => $term,
        ]);
    }

    /**
     * Authenticated Library Page file browse — complete Library, all orgs/owners/types.
     */
    public function files(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 401);

        $validated = $request->validate([
            'plant_production_category_id' => ['required', 'string', 'max:64'],
            'field_crop_id' => ['required', 'string', 'max:64'],
            'library_file_section' => ['required', 'string', 'in:'.implode(',', self::FILE_SECTIONS)],
        ]);

        $categoryId = $validated['plant_production_category_id'];
        $cropId = $validated['field_crop_id'];
        $sectionId = $validated['library_file_section'];

        $items = LibraryItem::query()
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
            ->get(['id', 'title', 'title_ar', 'file_disk', 'file_path', 'organization_id', 'owner_user_id', 'item_type']);

        return response()->json([
            'data' => $items->map(fn (LibraryItem $item): array => $this->presentFile($item))->values(),
        ]);
    }

    /**
     * Authenticated Library Page file open — any stored Library file, regardless of org/owner.
     */
    public function fileContent(Request $request, int $fileId): StreamedResponse
    {
        abort_unless($request->user() !== null, 401);

        $item = LibraryItem::query()
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

    public function store(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);
        $payload = $this->validatedPayload($request, $module);
        $tagIds = $payload['tag_ids'] ?? null;
        unset($payload['tag_ids']);

        $this->authorizePermission($request, $this->moduleManagePermission($request, $module));

        $record = $class::unguarded(fn () => $class::create([
            'organization_id' => $this->organization($request),
            ...$this->ownership()->assignOwnerFromSession($payload, $request->user()),
        ]));

        if ($module === 'items' && $tagIds) {
            OrganizationScopeValidator::assertLibraryTagIds($this->organization($request), $tagIds);
            $record->tags()->sync($tagIds);
            $record->load('tags');
        }

        return response()->json($this->presentItem($record, $module), 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $record = $this->findOwnedModuleRecord($request, $module, $class, $id);
        $payload = $this->validatedPayload($request, $module);
        $tagIds = $payload['tag_ids'] ?? null;
        unset($payload['tag_ids']);
        $record->update($this->ownership()->stripOwnerKeys($payload));

        if ($module === 'items' && is_array($tagIds)) {
            OrganizationScopeValidator::assertLibraryTagIds($this->organization($request), $tagIds);
            $record->tags()->sync($tagIds);
        }

        return response()->json($this->presentItem($record, $module));
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);

        return $this->ownedDestroy($request, $module, $class, $id);
    }

    private function presentItem(object $record, string $module): object
    {
        if ($module !== 'items') {
            return $record;
        }

        $record->loadMissing('tags');
        $record->setAttribute(
            'file',
            $this->media->toPublicMetadata($record->file_disk ?? null, $record->file_path ?? null)
        );
        $record->makeHidden(['file_disk', 'file_path']);

        return $record;
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
            'organization_id' => (int) $item->organization_id,
            'owner_user_id' => $item->owner_user_id !== null ? (int) $item->owner_user_id : null,
            'item_type' => $item->item_type,
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
}
