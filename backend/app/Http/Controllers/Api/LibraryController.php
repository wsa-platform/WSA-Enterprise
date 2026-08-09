<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{CropType, LibraryCategory, LibraryItem, LibraryTag};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    private const MODULES = [
        'categories' => [LibraryCategory::class, ['parent_id'=>['nullable','integer','exists:library_categories,id'], 'code'=>['required','string','max:32'], 'name'=>['required','string','max:255'], 'name_ar'=>['nullable','string','max:255']], ['parent_id'=>LibraryCategory::class]],
        'tags' => [LibraryTag::class, ['name'=>['required','string','max:64'], 'name_ar'=>['nullable','string','max:64']], []],
        'items' => [LibraryItem::class, ['category_id'=>['nullable','integer','exists:library_categories,id'], 'crop_type_id'=>['nullable','integer','exists:crop_types,id'], 'slug'=>['required','string','max:128'], 'title'=>['required','string','max:255'], 'title_ar'=>['nullable','string','max:255'], 'summary'=>['nullable','string'], 'summary_ar'=>['nullable','string'], 'content'=>['nullable','string'], 'content_ar'=>['nullable','string'], 'item_type'=>['sometimes','string','max:32'], 'author'=>['nullable','string','max:255'], 'source'=>['nullable','string','max:255'], 'locale'=>['sometimes','string','max:8'], 'publication_status'=>['sometimes','string','max:32'], 'published_at'=>['nullable','date'], 'file_disk'=>['nullable','string','max:32'], 'file_path'=>['nullable','string','max:255'], 'metadata'=>['nullable','array'], 'tag_ids'=>['nullable','array'], 'tag_ids.*'=>['integer','exists:library_tags,id']], ['category_id'=>LibraryCategory::class, 'crop_type_id'=>CropType::class]],
    ];

    private function config(string $module): array { abort_unless(isset(self::MODULES[$module]), 404); return self::MODULES[$module]; }
    private function organization(Request $request): int { return $request->user()->organizations()->firstOrFail()->id; }

    private function validatedPayload(Request $request, string $module): array
    {
        [, $rules, $relations] = $this->config($module);
        $data = $request->validate($rules);
        AgriculturalScopeValidator::assert($this->organization($request), $data, $relations);
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
            return response()->json($query->with('tags')->paginate((int) $request->query('per_page', 15)));
        }
        return response()->json($query->get());
    }

    public function search(Request $request): JsonResponse
    {
        $organizationId = $this->organization($request);
        $term = $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']])['q'];

        $query = LibraryItem::where('organization_id', $organizationId)
            ->where('publication_status', 'published')
            ->where(function ($builder) use ($term) {
                $builder->where('title', 'like', "%{$term}%")
                    ->orWhere('title_ar', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%")
                    ->orWhere('summary_ar', 'like', "%{$term}%");
            })
            ->with('tags');

        if ($tag = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $tag)->orWhere('name_ar', $tag));
        }

        return response()->json($query->latest('published_at')->paginate((int) $request->query('per_page', 15)));
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

        return response()->json($record, 201);
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

        return response()->json($module === 'items' ? $record->load('tags') : $record);
    }

    public function destroy(Request $request, string $module, int $id): JsonResponse
    {
        [$class] = $this->config($module);
        $class::where('organization_id', $this->organization($request))->findOrFail($id)->delete();
        return response()->json(status: 204);
    }
}
