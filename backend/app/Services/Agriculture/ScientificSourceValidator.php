<?php

namespace App\Services\Agriculture;

class ScientificSourceValidator
{
    public const UNCERTAINTY_MESSAGE = 'لا تتوفر حاليًا معلومات علمية موثقة كافية لهذا البند في المصادر العلمية المعتمدة المتاحة.';

    public const INSUFFICIENT_CROP_MESSAGE = 'لم يتم العثور على معلومات علمية موثقة كافية لهذا المحصول في المصادر المعتمدة المتاحة حاليًا.';

    /**
     * @param  array<string, mixed>  $source
     */
    public function isVerifiedSource(array $source): bool
    {
        $sourceType = strtolower(trim((string) ($source['source_type'] ?? '')));
        if ($sourceType === '' || ! ScientificSourceRegistry::isApprovedSourceType($sourceType)) {
            return false;
        }

        if (ScientificSourceRegistry::confidenceLevelFor($sourceType) === ScientificSourceRegistry::LEVEL_UNVERIFIED) {
            return false;
        }

        $organization = trim((string) ($source['organization'] ?? ''));
        $title = trim((string) ($source['title'] ?? ''));
        if ($organization === '' || $title === '') {
            return false;
        }

        $url = trim((string) ($source['url'] ?? ''));
        if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{content?: string, source?: array<string, mixed>, verified?: bool}  $section
     */
    public function isVerifiedSection(array $section): bool
    {
        if (($section['verified'] ?? false) !== true) {
            return false;
        }

        $content = trim((string) ($section['content'] ?? ''));
        if ($content === '' || $content === self::UNCERTAINTY_MESSAGE) {
            return false;
        }

        $source = $section['source'] ?? null;
        if (! is_array($source)) {
            return false;
        }

        return $this->isVerifiedSource($source);
    }
}
