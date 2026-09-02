<?php

namespace App\Services\Agriculture;

/**
 * Normalizes externally retrieved scientific text into human-readable Arabic explanations.
 */
class ScientificContentNormalizer
{
    /**
     * @param  array<string, mixed>  $source
     */
    public function normalizeForSection(
        string $rawContent,
        CropKnowledgeContext $context,
        string $sectionTitle,
        array $source,
    ): string {
        $content = trim($rawContent);
        if ($content === '') {
            return '';
        }

        $organization = trim((string) ($source['organization'] ?? ''));
        $year = $source['year'] ?? null;
        $sourceTitle = trim((string) ($source['title'] ?? ''));

        $attribution = $organization !== '' ? " صادر عن {$organization}" : '';
        if ($year !== null && $year !== '') {
            $attribution .= " ({$year})";
        }

        $documentLine = $sourceTitle !== '' ? "عنوان الوثيقة: {$sourceTitle}.\n\n" : '';

        return sprintf(
            "تتناول الأبحاث العلمية الموثقة هذا البند (%s) فيما يخص محصول %s.%s\n\n%s%s\n\n".
            'يُنصح بالرجوع إلى المصدر الأصلي للتفاصيل الكاملة وللتحقق من مدى انطباق النتائج على الظروف المحلية والإقليمية.',
            $sectionTitle,
            $context->cropName,
            $attribution,
            $documentLine,
            $content,
        );
    }
}
