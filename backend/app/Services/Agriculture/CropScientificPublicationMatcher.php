<?php

namespace App\Services\Agriculture;

/**
 * Generic relevance checks for indexed scientific publications against a crop context.
 */
class CropScientificPublicationMatcher
{
    /**
     * @param  list<string>  $extraTerms
     */
    public function isRelevant(CropKnowledgeContext $context, string $title, string $body, array $extraTerms = []): bool
    {
        $haystack = mb_strtolower(trim($title.' '.$body));
        if ($haystack === '') {
            return false;
        }

        $needles = array_merge(
            FieldCropTaxonomyCatalog::searchTermsFor($context->cropId),
            $extraTerms,
        );

        if ($context->scientificName !== '') {
            $needles[] = $context->scientificName;
            $genus = trim(explode(' ', $context->scientificName)[0] ?? '');
            if ($genus !== '') {
                $needles[] = $genus;
            }
        }

        $cropId = mb_strtolower(str_replace('-', ' ', $context->cropId));
        if ($cropId !== '' && strlen($cropId) > 2) {
            $needles[] = $cropId;
        }

        $cropName = mb_strtolower(trim($context->cropName));
        if ($cropName !== '' && mb_strlen($cropName) > 2) {
            $needles[] = $cropName;
        }

        foreach (array_unique(array_filter($needles)) as $needle) {
            $normalized = mb_strtolower(trim($needle));
            if ($normalized === '' || mb_strlen($normalized) < 3) {
                continue;
            }
            if (str_contains($haystack, $normalized)) {
                return true;
            }
        }

        return false;
    }
}
