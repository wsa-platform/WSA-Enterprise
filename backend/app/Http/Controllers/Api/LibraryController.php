<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Concerns\ManagesUserOwnedModules;
use App\Http\Controllers\Concerns\PaginatesOrganizationRecords;
use App\Http\Controllers\Controller;
use App\Models\{CropType, LibraryCategory, LibraryItem, LibraryTag};
use App\Services\Agriculture\Research\Persistence\ScientificKnowledgePersistenceService;
use App\Services\Media\MediaReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);

        if ($module !== 'items') {
            return $this->ownedIndex($request, $module, $class);
        }

        $this->authorizePermission($request, $this->moduleViewPermission($request, $module));
        $query = $this->scientificResearchAwareItemsQuery($request)->latest();

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

        return $this->paginateQuery($request, $query);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'library.view');
        $organizationId = $this->organization($request);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'crop_type_id' => ['nullable', 'integer'],
            'tag' => ['nullable', 'string', 'max:64'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $term = $validated['q'];
        $likeOperator = LibraryItem::query()->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $query = $this->scientificResearchAwareItemsQuery($request)
            ->where('publication_status', 'published')
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
            OrganizationScopeValidator::assert($organizationId, ['category_id' => $categoryId], ['category_id' => LibraryCategory::class]);
            $query->where('category_id', $categoryId);
        }

        if ($cropTypeId = $validated['crop_type_id'] ?? null) {
            OrganizationScopeValidator::assert($organizationId, ['crop_type_id' => $cropTypeId], ['crop_type_id' => CropType::class]);
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
     * Verified scientific research is a shared authenticated Library surface:
     * visible to any member with library.view without ownership/supervisor gates.
     * Other Library item types remain organization-owned services.
     *
     * @return Builder<LibraryItem>
     */
    private function scientificResearchAwareItemsQuery(Request $request): Builder
    {
        $organizationId = $this->organization($request);
        $user = $request->user();
        $canSupervise = $user !== null && $this->ownership()->canSupervise($user, $organizationId);
        $ownerColumn = config('service_ownership.owner_column', 'owner_user_id');

        return LibraryItem::query()->where(function (Builder $builder) use ($organizationId, $user, $canSupervise, $ownerColumn): void {
            $builder->where('item_type', ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH);

            $builder->orWhere(function (Builder $orgItems) use ($organizationId, $user, $canSupervise, $ownerColumn): void {
                $orgItems->where('organization_id', $organizationId)
                    ->where(function (Builder $types): void {
                        $types->whereNull('item_type')
                            ->orWhere('item_type', '!=', ScientificKnowledgePersistenceService::ITEM_TYPE_VERIFIED_RESEARCH);
                    });

                if (! $canSupervise && $user !== null) {
                    $orgItems->where($ownerColumn, $user->id);
                }
            });
        });
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
}
