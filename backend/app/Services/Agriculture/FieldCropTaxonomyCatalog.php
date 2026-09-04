<?php

namespace App\Services\Agriculture;

/**
 * Generic botanical taxonomy for all field crops in the platform selector.
 * Data is crop-taxonomy metadata — not institution-specific and not crop-specific logic.
 */
class FieldCropTaxonomyCatalog
{
    /**
     * @return array{scientific_name: string, synonyms: list<string>}|null
     */
    public static function entryFor(string $cropId): ?array
    {
        $entries = self::entries();

        return $entries[$cropId] ?? null;
    }

    public static function scientificNameFor(string $cropId): string
    {
        return (string) (self::entryFor($cropId)['scientific_name'] ?? '');
    }

    /**
     * @return list<string>
     */
    public static function searchTermsFor(string $cropId): array
    {
        $entry = self::entryFor($cropId);
        if ($entry === null) {
            return [];
        }

        $terms = array_merge(
            [$entry['scientific_name']],
            $entry['synonyms'],
        );

        $genus = trim(explode(' ', $entry['scientific_name'])[0] ?? '');
        if ($genus !== '' && ! in_array($genus, $terms, true)) {
            $terms[] = $genus;
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $term): string => trim($term),
            $terms,
        ))));
    }

    public static function resolveScientificName(string $cropId, string $provided = ''): string
    {
        $provided = trim($provided);
        if ($provided !== '') {
            return $provided;
        }

        return self::scientificNameFor($cropId);
    }

    /**
     * @return array<string, array{scientific_name: string, synonyms: list<string>}>
     */
    private static function entries(): array
    {
        return [
            'wheat' => ['scientific_name' => 'Triticum aestivum', 'synonyms' => ['wheat', 'bread wheat']],
            'corn' => ['scientific_name' => 'Zea mays', 'synonyms' => ['corn', 'maize']],
            'rice' => ['scientific_name' => 'Oryza sativa', 'synonyms' => ['rice', 'paddy']],
            'barley' => ['scientific_name' => 'Hordeum vulgare', 'synonyms' => ['barley']],
            'oats' => ['scientific_name' => 'Avena sativa', 'synonyms' => ['oats', 'oat']],
            'sorghum' => ['scientific_name' => 'Sorghum bicolor', 'synonyms' => ['sorghum', 'great millet']],
            'millet' => ['scientific_name' => 'Pennisetum glaucum', 'synonyms' => ['millet', 'pearl millet']],
            'rye' => ['scientific_name' => 'Secale cereale', 'synonyms' => ['rye']],
            'triticale' => ['scientific_name' => '× Triticosecale', 'synonyms' => ['triticale']],
            'sugarcane' => ['scientific_name' => 'Saccharum officinarum', 'synonyms' => ['sugarcane', 'sugar cane']],
            'sugar-beet' => ['scientific_name' => 'Beta vulgaris', 'synonyms' => ['sugar beet', 'sugarbeet']],
            'alfalfa' => ['scientific_name' => 'Medicago sativa', 'synonyms' => ['alfalfa', 'lucerne']],
            'clover' => ['scientific_name' => 'Trifolium alexandrinum', 'synonyms' => ['clover', 'berseem', 'trifolium']],
            'fodder-corn' => ['scientific_name' => 'Zea mays', 'synonyms' => ['fodder corn', 'silage maize', 'maize']],
            'fodder-sorghum' => ['scientific_name' => 'Sorghum bicolor', 'synonyms' => ['forage sorghum', 'sorghum']],
            'sudan-grass' => ['scientific_name' => 'Sorghum sudanense', 'synonyms' => ['sudan grass', 'sudangrass']],
            'sunflower' => ['scientific_name' => 'Helianthus annuus', 'synonyms' => ['sunflower']],
            'soybean' => ['scientific_name' => 'Glycine max', 'synonyms' => ['soybean', 'soya']],
            'sesame' => ['scientific_name' => 'Sesamum indicum', 'synonyms' => ['sesame', 'sesamum']],
            'peanut' => ['scientific_name' => 'Arachis hypogaea', 'synonyms' => ['peanut', 'groundnut']],
            'canola' => ['scientific_name' => 'Brassica napus', 'synonyms' => ['canola', 'rapeseed', 'oilseed rape']],
            'castor' => ['scientific_name' => 'Ricinus communis', 'synonyms' => ['castor', 'castor bean']],
            'fava-bean' => ['scientific_name' => 'Vicia faba', 'synonyms' => ['fava bean', 'broad bean', 'fababean']],
            'lentil' => ['scientific_name' => 'Lens culinaris', 'synonyms' => ['lentil']],
            'chickpea' => ['scientific_name' => 'Cicer arietinum', 'synonyms' => ['chickpea', 'garbanzo']],
            'pea' => ['scientific_name' => 'Pisum sativum', 'synonyms' => ['pea', 'garden pea']],
            'cowpea' => ['scientific_name' => 'Vigna unguiculata', 'synonyms' => ['cowpea', 'black-eyed pea']],
            'cotton' => ['scientific_name' => 'Gossypium hirsutum', 'synonyms' => ['cotton', 'upland cotton']],
            'flax' => ['scientific_name' => 'Linum usitatissimum', 'synonyms' => ['flax', 'linseed']],
            'hemp' => ['scientific_name' => 'Cannabis sativa', 'synonyms' => ['hemp', 'industrial hemp']],
            'jute' => ['scientific_name' => 'Corchorus olitorius', 'synonyms' => ['jute']],
            'tobacco' => ['scientific_name' => 'Nicotiana tabacum', 'synonyms' => ['tobacco']],
            'tomato' => ['scientific_name' => 'Solanum lycopersicum', 'synonyms' => ['tomato', 'tomatoes', 'lycopersicon esculentum']],
            'pepper' => ['scientific_name' => 'Capsicum annuum', 'synonyms' => ['pepper', 'bell pepper', 'chili', 'chilli', 'chili pepper', 'sweet pepper']],
            'ginger' => ['scientific_name' => 'Zingiber officinale', 'synonyms' => ['ginger', 'ginger root']],
        ];
    }
}
