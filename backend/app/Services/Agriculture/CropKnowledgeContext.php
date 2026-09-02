<?php

namespace App\Services\Agriculture;

/**
 * Generic crop knowledge request context — no crop-specific logic.
 *
 * @phpstan-type CropContextArray array{
 *   selected_crop_id: string,
 *   selected_crop_name: string,
 *   selected_category_id?: string,
 *   selected_category_name?: string,
 *   knowledge_option?: string,
 *   scientific_name?: string,
 *   locale?: string
 * }
 */
final class CropKnowledgeContext
{
    public function __construct(
        public readonly string $cropId,
        public readonly string $cropName,
        public readonly string $categoryId,
        public readonly string $categoryName,
        public readonly string $knowledgeOption,
        public readonly string $scientificName = '',
        public readonly string $locale = 'ar',
    ) {}

    /**
     * @param  CropContextArray  $input
     */
    public static function fromArray(array $input): self
    {
        $cropId = (string) ($input['selected_crop_id'] ?? '');
        $scientificName = FieldCropTaxonomyCatalog::resolveScientificName(
            $cropId,
            (string) ($input['scientific_name'] ?? ''),
        );

        return new self(
            cropId: $cropId,
            cropName: (string) ($input['selected_crop_name'] ?? ''),
            categoryId: (string) ($input['selected_category_id'] ?? ''),
            categoryName: (string) ($input['selected_category_name'] ?? ''),
            knowledgeOption: (string) ($input['knowledge_option'] ?? 'farming-needs'),
            scientificName: $scientificName,
            locale: (string) ($input['locale'] ?? 'ar'),
        );
    }

    /** @return CropContextArray */
    public function toArray(): array
    {
        return [
            'selected_crop_id' => $this->cropId,
            'selected_crop_name' => $this->cropName,
            'selected_category_id' => $this->categoryId,
            'selected_category_name' => $this->categoryName,
            'knowledge_option' => $this->knowledgeOption,
            'scientific_name' => $this->scientificName,
            'locale' => $this->locale,
        ];
    }
}
