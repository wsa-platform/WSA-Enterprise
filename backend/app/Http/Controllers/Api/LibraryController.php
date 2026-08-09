<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesOrganization;
use App\Http\Controllers\Controller;
use App\Models\{CropType, LibraryCategory, LibraryItem, LibraryTag};
use App\Services\Media\MediaReferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    use ResolvesOrganization;

    private const MODULES = [
        'categories' => [LibraryCategory::class, ['parent_id'=>['nullable','integer','exists:library_categories,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255']], ['parent_id'=>LibraryCategory::class]],
        'tags' => [LibraryTag::class, ['name'=>['required','string','max:64'], 'name_ar'=>['nullable','string','max:64']], []],
        'items' => [LibraryItem::class, ['category_id'=>['nullable','integer','exists:library_categories,id'], 'crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'slug'=>['required','string','max:128'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'summary'=>['nullable','string'], 'summary_ar'=>['nullable','string'], 'content'=>['nullable','string'], 'content_ar'=>['nullable','string'], 'item_type'=>['sometimes','string','max:32'], 'author'=>['nullable','string','max:255'], 'source'=>['nullable','string','max:255'], 'locale'=>['sometimes','string','max:8'], 'publication_status'=>['sometimes','string','max:32'], 'published_at'=>['nullable','date'], 'file_disk'=>['nullable','string','max:32'], 'file_path'=>['nullable','string','max:255'], 'metadata'=>['nullable','array'], 'tag_ids'=>['nullable','array'], 'tag_ids.*'=>['integer','exists:library_tags,id']], ['category_id'=>LibraryCategory::class, 'crop_type_id'=>CropType::class]],
    ];

    public function __construct(private MediaReferenceService $media) {}

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        AgriculturalScopeValidator::assert($this->organization($request), $data, $relations);

        if ($module === 'items') {
            $data = $this->media->validateAndSanitize($data);
        }

        return $data;
    }

    public function index(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);
        $query = $class::where('organization_id', $this->organization($request))->latest();
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

            return response()->json(
                $query->with(['tags', 'category:id,name,name_ar', 'cropType:id,code,name'])
                    ->paginate((int) $request->query('per_page', 15))
            );
        }

        return response()->json($query->get());
    }

    public function search(Request $request): JsonResponse
    {
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

        $query = LibraryItem::where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($builder) use ($term, $likeOperator) {
                $builder->where('title', $likeOperator, "%{$term}%")
                    ->orWhere('title_ar', $likeOperator, "%{$term}%")
                    ->orWhere('summary', $likeOperator, "%{$term}%")
                    ->orWhere('summary_ar', $likeOperator, "%{$term}%");
            })
            ->with(['tags', 'category:id,name,name_ar', 'cropType:id,code,name']);

        if ($categoryId = $validated['category_id'] ?? null) {
            AgriculturalScopeValidator::assert($organizationId, ['category_id' => $categoryId], ['category_id' => LibraryCategory::class]);
            $query->where('category_id', $categoryId);
        }

        if ($cropTypeId = $validated['crop_type_id'] ?? null) {
            AgriculturalScopeValidator::assert($organizationId, ['crop_type_id' => $cropTypeId], ['crop_type_id' => CropType::class]);
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

    public function store(Request $request, string $module): JsonResponse
    {
        [$class] = $this->config($module);
        $payload = $this->validatedPayload($request, $module);
        $tagIds = $payload['tag_ids'] ?? null;
        unset($payload['tag_ids']);

        $record = $class::create(['organization_id'=>$this->organization($request), ...$payload]);

        if ($module === 'items' && $tagIds) {
            $record->tags()->sync($tagIds);
            $record->load('tags');
        }

        return response()->json($this->presentItem($record, $module), 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $record = $class::where('organization_id', $this->organization($request))->findOrFail($id);
        $payload = $this->validatedPayload($request, $module);
        $tagIds = $payload['tag_ids'] ?? null;
        unset($payload['tag_ids']);
        $record->update($payload);

        if ($module === 'items' && is_array($tagIds)) {
            $record->tags()->sync($tagIds);
        }

        return response()->json($this->presentItem($record, $module));
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();

        return response()->json(status: 204);
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
