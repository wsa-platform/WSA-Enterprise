<?php

namespace App\Services\Agriculture;

/**
 * Extensible registry of approved scientific source categories and confidence levels.
 * This is not a closed whitelist of institutions — new source types can be registered.
 */
class ScientificSourceRegistry
{
    public const LEVEL_PEER_REVIEWED = 'A';

    public const LEVEL_OFFICIAL_RESEARCH = 'A';

    public const LEVEL_EXTENSION_MANUAL = 'B';

    public const LEVEL_SUPPORTING = 'C';

    public const LEVEL_UNVERIFIED = 'D';

    /** @return list<array{key: string, label_ar: string, label_en: string, level: string}> */
    public static function categories(): array
    {
        return [
            ['key' => 'peer_reviewed_journal', 'label_ar' => 'مجلة علمية محكمة', 'label_en' => 'Peer-reviewed journal', 'level' => self::LEVEL_PEER_REVIEWED],
            ['key' => 'university_research', 'label_ar' => 'بحث جامعي رسمي', 'label_en' => 'Official university research', 'level' => self::LEVEL_OFFICIAL_RESEARCH],
            ['key' => 'research_institute', 'label_ar' => 'مركز أو معهد بحوث زراعي', 'label_en' => 'Agricultural research center', 'level' => self::LEVEL_OFFICIAL_RESEARCH],
            ['key' => 'government', 'label_ar' => 'مؤسسة حكومية زراعية', 'label_en' => 'Government agricultural institution', 'level' => self::LEVEL_OFFICIAL_RESEARCH],
            ['key' => 'international_organization', 'label_ar' => 'منظمة زراعية دولية معترف بها', 'label_en' => 'Recognized international organization', 'level' => self::LEVEL_OFFICIAL_RESEARCH],
            ['key' => 'extension_publication', 'label_ar' => 'نشرة إرشاد زراعي رسمية', 'label_en' => 'Official extension publication', 'level' => self::LEVEL_EXTENSION_MANUAL],
            ['key' => 'technical_manual', 'label_ar' => 'دليل تقني معتمد', 'label_en' => 'Recognized technical manual', 'level' => self::LEVEL_EXTENSION_MANUAL],
            ['key' => 'scientific_book', 'label_ar' => 'كتاب علمي أكاديمي', 'label_en' => 'Academic scientific book', 'level' => self::LEVEL_EXTENSION_MANUAL],
            ['key' => 'supporting_verified', 'label_ar' => 'مصدر داعم موثق', 'label_en' => 'Supporting verified source', 'level' => self::LEVEL_SUPPORTING],
        ];
    }

    /** @return list<string> */
    public static function approvedSourceTypes(): array
    {
        return array_column(self::categories(), 'key');
    }

    public static function confidenceLevelFor(string $sourceType): string
    {
        foreach (self::categories() as $category) {
            if ($category['key'] === $sourceType) {
                return $category['level'];
            }
        }

        return self::LEVEL_UNVERIFIED;
    }

    public static function isApprovedSourceType(string $sourceType): bool
    {
        return in_array($sourceType, self::approvedSourceTypes(), true);
    }
}
