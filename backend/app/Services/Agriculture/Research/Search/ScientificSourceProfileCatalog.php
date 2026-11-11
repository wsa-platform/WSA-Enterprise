<?php

namespace App\Services\Agriculture\Research\Search;

/**
 * Static capability catalog for Stage-3 sources and expansion-ready stubs.
 *
 * Not a runtime adapter registry. Does not execute search.
 * Future sources may appear here as inactive profiles without CSQ changes.
 */
final class ScientificSourceProfileCatalog
{
    /** @var array<string, ScientificSourceProfile>|null */
    private static ?array $profiles = null;

    public static function get(string $sourceKey): ?ScientificSourceProfile
    {
        $key = strtolower(trim($sourceKey));

        return self::all()[$key] ?? null;
    }

    /**
     * @return array<string, ScientificSourceProfile>
     */
    public static function all(): array
    {
        return self::$profiles ??= self::build();
    }

    /**
     * @return list<string>
     */
    public static function activeSourceKeys(): array
    {
        $keys = [];
        foreach (self::all() as $key => $profile) {
            if ($profile->active) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, ScientificSourceProfile>
     */
    private static function build(): array
    {
        $literatureIdentity = ['entity', 'target', 'process', 'property', 'relation'];
        $literatureConstraints = ['geography', 'time', 'context'];

        return [
            'openalex' => new ScientificSourceProfile(
                sourceKey: 'openalex',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['doi', 'openalex_id'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            'crossref' => new ScientificSourceProfile(
                sourceKey: 'crossref',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['doi'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            'semantic_scholar' => new ScientificSourceProfile(
                sourceKey: 'semantic_scholar',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['doi', 'paper_id'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            'fao_stat' => new ScientificSourceProfile(
                sourceKey: 'fao_stat',
                modality: ScientificSourceQueryModality::SCIENTIFIC_DATA,
                supportedIdentityFields: ['entity', 'property'],
                supportedConstraintFields: ['geography', 'time'],
                queryStyle: 'structured_filters',
                identifierFields: ['item_code', 'element_code', 'area_code', 'year'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            'consensus' => new ScientificSourceProfile(
                sourceKey: 'consensus',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: [],
                active: false,
                maxQueriesPerRequest: 1,
            ),
            // G1-01 bounded ODS subset ACTIVE (2026-10-18): LocalIndex search; full corpus not claimed.
            'agris' => new ScientificSourceProfile(
                sourceKey: 'agris',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['doi', 'agris_id'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            // G1-17 limited projection ACTIVE (2026-10-18): FARMACY≠FARMACY_NEW; ETHNOBOT traditional-use.
            'dr_duke' => new ScientificSourceProfile(
                sourceKey: 'dr_duke',
                modality: ScientificSourceQueryModality::SCIENTIFIC_DATA,
                supportedIdentityFields: ['entity'],
                supportedConstraintFields: [],
                queryStyle: 'natural_language',
                identifierFields: ['fnfnum', 'chem', 'chemid'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            // G5-04 activated: species search accepts free-text q; geography/time and
            // taxon_key filters are not claimed until a Capability decision backs them.
            'gbif' => new ScientificSourceProfile(
                sourceKey: 'gbif',
                modality: ScientificSourceQueryModality::SCIENTIFIC_DATA,
                supportedIdentityFields: ['entity'],
                supportedConstraintFields: [],
                queryStyle: 'natural_language',
                identifierFields: [],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            // G1-02 owner ACCEPT + CapVer AUTHORIZE (2026-10-18): OAI-PMH LocalIndex search.
            'organic_eprints' => new ScientificSourceProfile(
                sourceKey: 'organic_eprints',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['oai_identifier'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            // G6-20 owner ACCEPT + CapVer AUTHORIZE (2026-10-18): BHL PublicationSearch.
            'bhl' => new ScientificSourceProfile(
                sourceKey: 'bhl',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['bhl_part_id', 'bhl_title_id'],
                active: true,
                maxQueriesPerRequest: 1,
            ),
            'pubmed' => new ScientificSourceProfile(
                sourceKey: 'pubmed',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['pmid', 'doi'],
                active: false,
            ),
            'europe_pmc' => new ScientificSourceProfile(
                sourceKey: 'europe_pmc',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['pmcid', 'doi'],
                active: false,
            ),
            'agricola' => new ScientificSourceProfile(
                sourceKey: 'agricola',
                modality: ScientificSourceQueryModality::LITERATURE,
                supportedIdentityFields: $literatureIdentity,
                supportedConstraintFields: $literatureConstraints,
                queryStyle: 'natural_language',
                identifierFields: ['doi'],
                active: false,
            ),
        ];
    }
}
