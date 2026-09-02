<?php

namespace App\Services\Agriculture;

class CropKnowledgeOptionCatalog
{
    /** @return list<array{key: string, title_ar: string, implemented: bool}> */
    public static function options(): array
    {
        return [
            ['key' => 'farming-needs', 'title_ar' => 'زراعة واحتياجات المحصول', 'implemented' => true],
            ['key' => 'scientific-research', 'title_ar' => 'الأبحاث العلمية', 'implemented' => true],
            ['key' => 'industries', 'title_ar' => 'الصناعات القائمة', 'implemented' => true],
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

    public static function titleFor(string $knowledgeOption, string $cropName): string
    {
        return match ($knowledgeOption) {
            'farming-needs' => 'زراعة واحتياجات محصول '.$cropName,
            'scientific-research' => 'الأبحاث العلمية لمحصول '.$cropName,
            'industries' => 'الصناعات القائمة على '.$cropName,
            default => 'معرفة محصول '.$cropName,
        };
    }
}
