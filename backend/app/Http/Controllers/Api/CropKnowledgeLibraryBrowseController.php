<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesOrganizationAccess;
use App\Http\Controllers\Controller;
use App\Models\LibraryItem;
use App\Services\Agriculture\CropKnowledgeOptionCatalog;
use App\Services\Agriculture\CropKnowledgeSectionCatalog;
use App\Services\Agriculture\ScientificSourceValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CropKnowledgeLibraryBrowseController extends Controller
{
    use AuthorizesOrganizationAccess;

    public function tree(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'library.view');
        $organizationId = $this->organization($request);

        $items = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('item_type', 'crop_cultivation_profile')
            ->where('publication_status', 'published')
            ->orderBy('title_ar')
            ->get(['id', 'slug', 'title_ar', 'metadata', 'published_at']);

        $categories = [];
        foreach ($items as $item) {
            $metadata = is_array($item->metadata) ? $item->metadata : [];
            $categoryId = (string) ($metadata['field_crop_category_id'] ?? 'uncategorized');
            $categoryName = (string) ($metadata['field_crop_category_name'] ?? 'غير مصنف');
            $cropId = (string) ($metadata['field_crop_id'] ?? '');
            $cropName = (string) ($metadata['field_crop_name'] ?? $item->title_ar);
            $knowledgeOption = (string) ($metadata['knowledge_option'] ?? $metadata['service_option'] ?? 'farming-needs');

            $categories[$categoryId] ??= [
                'id' => $categoryId,
                'name' => $categoryName,
                'crops' => [],
            ];

            $categories[$categoryId]['crops'][$cropId] ??= [
                'id' => $cropId,
                'name' => $cropName,
                'scientific_name' => (string) ($metadata['scientific_name'] ?? ''),
                'options' => [],
            ];

            $sectionDefinitions = CropKnowledgeSectionCatalog::sectionsFor($knowledgeOption);
            $storedSections = is_array($metadata['cultivation_sections'] ?? null) ? $metadata['cultivation_sections'] : [];
            $validator = app(ScientificSourceValidator::class);

            $categories[$categoryId]['crops'][$cropId]['options'][$knowledgeOption] = [
                'key' => $knowledgeOption,
                'title' => CropKnowledgeOptionCatalog::titleFor($knowledgeOption, $cropName),
                'library_item_id' => $item->id,
                'slug' => $item->slug,
                'section_count' => count($sectionDefinitions),
                'sections' => array_map(function (array $definition) use ($storedSections, $validator): array {
                    $key = $definition['key'];
                    $section = is_array($storedSections[$key] ?? null) ? $storedSections[$key] : null;

                    return [
                        'key' => $key,
                        'title' => $definition['title'],
                        'verified' => is_array($section) && $validator->isVerifiedSection($section),
                    ];
                }, $sectionDefinitions),
            ];
        }

        $tree = array_values(array_map(function (array $category) {
            $category['crops'] = array_values(array_map(function (array $crop) {
                $crop['options'] = array_values($crop['options']);

                return $crop;
            }, $category['crops']));

            return $category;
        }, $categories));

        return response()->json([
            'categories' => $tree,
            'knowledge_options' => CropKnowledgeOptionCatalog::options(),
        ]);
    }

    public function show(Request $request, int $itemId): JsonResponse
    {
        $this->authorizePermission($request, 'library.view');
        $organizationId = $this->organization($request);

        $item = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('id', $itemId)
            ->where('item_type', 'crop_cultivation_profile')
            ->firstOrFail();

        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $knowledgeOption = (string) ($metadata['knowledge_option'] ?? $metadata['service_option'] ?? 'farming-needs');
        $sections = is_array($metadata['cultivation_sections'] ?? null) ? $metadata['cultivation_sections'] : [];
        $references = is_array($metadata['scientific_references'] ?? null) ? $metadata['scientific_references'] : [];

        $formattedSections = [];
        foreach (CropKnowledgeSectionCatalog::sectionsFor($knowledgeOption) as $definition) {
            $key = $definition['key'];
            $section = $sections[$key] ?? ['content' => ScientificSourceValidator::UNCERTAINTY_MESSAGE, 'verified' => false];
            $formattedSections[] = [
                'key' => $key,
                'title' => $definition['title'],
                'content' => (string) ($section['content'] ?? ''),
                'source' => is_array($section['source'] ?? null) ? $section['source'] : null,
                'verified' => (bool) ($section['verified'] ?? false),
            ];
        }

        return response()->json([
            'id' => $item->id,
            'slug' => $item->slug,
            'title_ar' => $item->title_ar,
            'crop' => [
                'id' => (string) ($metadata['field_crop_id'] ?? ''),
                'name' => (string) ($metadata['field_crop_name'] ?? ''),
                'category_id' => (string) ($metadata['field_crop_category_id'] ?? ''),
                'category_name' => (string) ($metadata['field_crop_category_name'] ?? ''),
                'scientific_name' => (string) ($metadata['scientific_name'] ?? ''),
            ],
            'knowledge_option' => $knowledgeOption,
            'sections' => $formattedSections,
            'references' => $references,
            'content_ar' => $item->content_ar,
            'published_at' => $item->published_at,
        ]);
    }
}
