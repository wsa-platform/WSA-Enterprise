<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Evidence-based language capabilities for the ten Stage-3 production keys (ISS-SRC-002).
 */
final class ScientificSourceLanguageCapabilityCatalog
{
    /** @return array<string, ScientificSourceLanguageCapability> */
    public static function all(): array
    {
        $rows = [];
        foreach (self::definitions() as $def) {
            $rows[$def->sourceKey] = $def;
        }

        return $rows;
    }

    public static function for(string $sourceKey): ?ScientificSourceLanguageCapability
    {
        return self::all()[$sourceKey] ?? null;
    }

    /** @return list<ScientificSourceLanguageCapability> */
    private static function definitions(): array
    {
        $scholarly = static fn (string $key): ScientificSourceLanguageCapability => new ScientificSourceLanguageCapability(
            sourceKey: $key,
            queryInterfaceLanguages: ['en'],
            adaptedQueryLanguages: ['en'],
            indexedContentLanguages: ['en', 'unknown'],
            liveVerifiedLanguages: ['en'],
            adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_ADAPTED_QUERY,
            evidenceReference: 'ISS-SRC-002 scholarly HTTP adapters; English retrieval compilation (ADR-022 §15)',
        );

        $localIndex = static fn (string $key): ScientificSourceLanguageCapability => new ScientificSourceLanguageCapability(
            sourceKey: $key,
            queryInterfaceLanguages: ['en'],
            adaptedQueryLanguages: ['en'],
            indexedContentLanguages: ['en'],
            liveVerifiedLanguages: ['en'],
            adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_ADAPTED_QUERY,
            evidenceReference: 'ISS-SRC-002 LocalIndex FTS unicode61; English-dominant corpora; Arabic FTS AND fails without compilation',
        );

        return [
            $scholarly('openalex'),
            $scholarly('crossref'),
            $scholarly('semantic_scholar'),
            new ScientificSourceLanguageCapability(
                sourceKey: 'consensus',
                queryInterfaceLanguages: ['en'],
                adaptedQueryLanguages: ['en'],
                indexedContentLanguages: ['en', 'unknown'],
                liveVerifiedLanguages: [],
                adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_UNKNOWN,
                evidenceReference: 'OPTIONAL_EXPLICIT_ONLY; language not live-verified in this mission',
            ),
            new ScientificSourceLanguageCapability(
                sourceKey: 'fao_stat',
                queryInterfaceLanguages: ['en'],
                adaptedQueryLanguages: ['en'],
                indexedContentLanguages: ['unknown'],
                liveVerifiedLanguages: [],
                adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_ADAPTED_QUERY,
                evidenceReference: 'FAOSTAT Developer Portal lang default en; structured filters',
            ),
            $localIndex('agris'),
            $localIndex('organic_eprints'),
            $localIndex('dr_duke'),
            new ScientificSourceLanguageCapability(
                sourceKey: 'gbif',
                queryInterfaceLanguages: ['en', 'la'],
                adaptedQueryLanguages: ['en', 'la'],
                indexedContentLanguages: ['en', 'la', 'unknown'],
                liveVerifiedLanguages: ['en', 'la'],
                adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_ADAPTED_QUERY,
                evidenceReference: 'GBIF scientific-name / occurrence; Latin binomials',
            ),
            new ScientificSourceLanguageCapability(
                sourceKey: 'bhl',
                queryInterfaceLanguages: ['en'],
                adaptedQueryLanguages: ['en'],
                indexedContentLanguages: ['en', 'unknown'],
                liveVerifiedLanguages: ['en'],
                adapterLanguageSupport: ScientificSourceLanguageCapability::SUPPORT_ADAPTED_QUERY,
                evidenceReference: 'BHL heritage literature search; English query interface',
            ),
        ];
    }
}
