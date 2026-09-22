<?php

namespace App\Services\Agriculture;

class CropKnowledgeOptionCatalog
{
    /**
     * @return list<array{
     *   key: string,
     *   title_ar: string,
     *   title_en: string,
     *   title_fr: string,
     *   title_tr: string,
     *   implemented: bool
     * }>
     */
    public static function options(): array
    {
        return [
            [
                'key' => 'farming-needs',
                'title_ar' => 'زراعة واحتياجات المحصول',
                'title_en' => 'Crop cultivation and needs',
                'title_fr' => 'Culture et besoins de la culture',
                'title_tr' => 'Ürün yetiştirme ve ihtiyaçları',
                'implemented' => true,
            ],
            [
                'key' => 'scientific-research',
                'title_ar' => 'الأبحاث العلمية',
                'title_en' => 'Scientific research',
                'title_fr' => 'Recherche scientifique',
                'title_tr' => 'Bilimsel araştırma',
                'implemented' => true,
            ],
            [
                'key' => 'industries',
                'title_ar' => 'الصناعات القائمة',
                'title_en' => 'Related industries',
                'title_fr' => 'Industries associées',
                'title_tr' => 'İlgili endüstriler',
                'implemented' => true,
            ],
        ];
    }

    public static function isImplemented(string $knowledgeOption): bool
    {
        foreach (self::options() as $option) {
            if ($option['key'] === $knowledgeOption) {
                return (bool) $option['implemented'];
            }
        }

        return false;
    }

    /**
     * Short option title for a UI language. Option IDs remain language-independent.
     * Unknown languages fall back to Arabic (existing Arabic-first contract).
     */
    public static function optionTitle(string $knowledgeOption, string $language = 'ar'): string
    {
        $language = self::normalizeLanguage($language);
        foreach (self::options() as $option) {
            if ($option['key'] !== $knowledgeOption) {
                continue;
            }

            return match ($language) {
                'en' => (string) $option['title_en'],
                'fr' => (string) $option['title_fr'],
                'tr' => (string) $option['title_tr'],
                default => (string) $option['title_ar'],
            };
        }

        return $knowledgeOption;
    }

    /**
     * Profile title including crop display name.
     * Default language remains Arabic for backward compatibility with existing consumers.
     */
    public static function titleFor(string $knowledgeOption, string $cropName, string $language = 'ar'): string
    {
        $language = self::normalizeLanguage($language);

        return match ($knowledgeOption) {
            'farming-needs' => match ($language) {
                'en' => 'Crop cultivation and needs for '.$cropName,
                'fr' => 'Culture et besoins pour '.$cropName,
                'tr' => $cropName.' için yetiştirme ve ihtiyaçlar',
                default => 'زراعة واحتياجات محصول '.$cropName,
            },
            'scientific-research' => match ($language) {
                'en' => 'Scientific research for '.$cropName,
                'fr' => 'Recherche scientifique pour '.$cropName,
                'tr' => $cropName.' için bilimsel araştırma',
                default => 'الأبحاث العلمية لمحصول '.$cropName,
            },
            'industries' => match ($language) {
                'en' => 'Industries based on '.$cropName,
                'fr' => 'Industries basées sur '.$cropName,
                'tr' => $cropName.' temelli endüstriler',
                default => 'الصناعات القائمة على '.$cropName,
            },
            default => match ($language) {
                'en' => 'Crop knowledge for '.$cropName,
                'fr' => 'Connaissance de la culture '.$cropName,
                'tr' => $cropName.' ürün bilgisi',
                default => 'معرفة محصول '.$cropName,
            },
        };
    }

    private static function normalizeLanguage(string $language): string
    {
        $language = strtolower(substr(trim($language), 0, 2));

        return in_array($language, ['ar', 'en', 'fr', 'tr'], true) ? $language : 'ar';
    }
}
