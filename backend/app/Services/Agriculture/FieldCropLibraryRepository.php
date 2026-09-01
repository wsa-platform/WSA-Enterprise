<?php

namespace App\Services\Agriculture;

use App\Models\LibraryItem;
use Illuminate\Support\Arr;

class FieldCropLibraryRepository
{
    public function slugFor(string $cropId, string $serviceOption = 'farming-needs'): string
    {
        return sprintf('field-crop-%s-%s', $cropId, $serviceOption);
    }

    public function findFarmingNeedsItem(int $organizationId, string $cropId): ?LibraryItem
    {
        $slug = $this->slugFor($cropId);

        $bySlug = LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->first();

        if ($bySlug !== null) {
            return $bySlug;
        }

        return LibraryItem::query()
            ->where('organization_id', $organizationId)
            ->where('metadata->field_crop_id', $cropId)
            ->where('metadata->service_option', 'farming-needs')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $cropContext
     * @param  array<string, array{content: string, source?: array<string, mixed>, verified?: bool}>  $sections
     */
    public function mergeSections(
        int $organizationId,
        array $cropContext,
        array $sections,
        ?int $ownerUserId = null,
    ): LibraryItem {
        $cropId = (string) ($cropContext['selected_crop_id'] ?? '');
        $cropName = (string) ($cropContext['selected_crop_name'] ?? '');
        $categoryId = (string) ($cropContext['selected_category_id'] ?? '');
        $categoryName = (string) ($cropContext['selected_category_name'] ?? '');

        $item = $this->findFarmingNeedsItem($organizationId, $cropId) ?? new LibraryItem;
        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $existingSections = is_array($metadata['cultivation_sections'] ?? null)
            ? $metadata['cultivation_sections']
            : [];

        foreach ($sections as $key => $section) {
            if (isset($existingSections[$key]) && ($existingSections[$key]['verified'] ?? false) === true) {
                continue;
            }

            if (! isset($existingSections[$key]) || trim((string) ($existingSections[$key]['content'] ?? '')) === '') {
                $existingSections[$key] = $section;
            }
        }

        $references = is_array($metadata['scientific_references'] ?? null)
            ? $metadata['scientific_references']
            : [];
        foreach ($sections as $section) {
            if (! is_array($section['source'] ?? null)) {
                continue;
            }
            $references[] = $section['source'];
        }
        $references = array_values(Arr::keyBy($references, fn (array $ref) => (string) ($ref['url'] ?? json_encode($ref))));

        $metadata = array_merge($metadata, [
            'field_crop_id' => $cropId,
            'field_crop_category_id' => $categoryId,
            'field_crop_category_name' => $categoryName,
            'field_crop_name' => $cropName,
            'service_option' => 'farming-needs',
            'cultivation_sections' => $existingSections,
            'scientific_references' => $references,
        ]);

        $item->organization_id = $organizationId;
        if ($ownerUserId !== null && ! $item->exists) {
            $item->owner_user_id = $ownerUserId;
        }
        $item->slug = $this->slugFor($cropId);
        $item->title = sprintf('Farming needs profile: %s', $cropId);
        $item->title_ar = sprintf('زراعة واحتياجات محصول %s', $cropName);
        $item->summary_ar = sprintf('ملف زراعي علمي لمحصول %s — زراعة واحتياجات المحصول.', $cropName);
        $item->item_type = 'crop_cultivation_profile';
        $item->locale = 'ar';
        $item->publication_status = 'published';
        $item->published_at = $item->published_at ?? now();
        $item->metadata = $metadata;
        $item->content_ar = $this->renderMarkdownProfile($cropName, $existingSections, $references);
        $item->save();

        return $item->fresh();
    }

    /**
     * @param  array<string, array{content: string, source?: array<string, mixed>}>  $sections
     * @param  list<array<string, mixed>>  $references
     */
    private function renderMarkdownProfile(string $cropName, array $sections, array $references): string
    {
        $lines = ['# زراعة واحتياجات محصول '.$cropName, ''];

        foreach (FieldCropCultivationSectionCatalog::sections() as $definition) {
            $key = $definition['key'];
            $section = $sections[$key] ?? null;
            if (! is_array($section)) {
                continue;
            }

            $lines[] = '## '.$definition['title'];
            $lines[] = (string) ($section['content'] ?? '');
            if (is_array($section['source'] ?? null)) {
                $source = $section['source'];
                $lines[] = '';
                $lines[] = 'المصدر:';
                $lines[] = ($source['organization'] ?? '').' — '.($source['title'] ?? '');
                if (! empty($source['url'])) {
                    $lines[] = (string) $source['url'];
                }
            }
            $lines[] = '';
        }

        if ($references !== []) {
            $lines[] = '## المراجع العلمية';
            foreach ($references as $reference) {
                $lines[] = '- '.($reference['organization'] ?? '').' — '.($reference['title'] ?? '');
                if (! empty($reference['url'])) {
                    $lines[] = '  '.(string) $reference['url'];
                }
            }
        }

        return implode("\n", $lines);
    }
}
