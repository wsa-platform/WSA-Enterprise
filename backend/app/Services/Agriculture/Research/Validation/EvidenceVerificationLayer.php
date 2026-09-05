<?php

namespace App\Services\Agriculture\Research\Validation;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificSearchResult;

/**
 * Evidence Verification Layer — orchestration over existing Directness / Matcher / Quality.
 *
 * Does NOT replace Stage 4 validators. Refines directness labels and enforces priority:
 * claim > intent > topic > entity > geo > environment > quality > semantic > citations.
 * Semantic similarity and citation counts alone cannot force DIRECT.
 *
 * Verification labels: DIRECT / SUPPORTED / RELATED / IRRELEVANT / GEOGRAPHIC_MISMATCH
 */
class EvidenceVerificationLayer
{
    public const LABEL_DIRECT = 'DIRECT';

    public const LABEL_SUPPORTED = 'SUPPORTED';

    public const LABEL_RELATED = 'RELATED';

    public const LABEL_IRRELEVANT = 'IRRELEVANT';

    public const LABEL_GEOGRAPHIC_MISMATCH = 'GEOGRAPHIC_MISMATCH';

    /** @var list<string> */
    private const LAND_TYPE_OFFTOPIC_MARKERS = [
        'gerbera', 'rose', 'roses', 'cucumber', 'cucumbers',
        'polyhouse', 'greenhouse', 'greenhouses', 'hydroponics', 'hydroponic',
        'protected cultivation', 'soilless',
    ];

    /** @var list<string> */
    private const PROTECTED_ENV_MARKERS = [
        'greenhouse', 'greenhouses', 'polyhouse', 'polyhouses',
        'protected cultivation', 'protected agriculture', 'hydroponics', 'hydroponic',
        'soilless', 'glasshouse', 'tunnel house',
    ];

    /** @var list<string> */
    private const OPEN_FIELD_MARKERS = [
        'open field', 'open-field', 'openfield', 'field cultivation', 'field-grown',
        'rainfed', 'rain-fed', 'outdoor cultivation', 'حقل مفتوح', 'زراعة مكشوفة',
    ];

    public function __construct(
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
    ) {}

    /**
     * Assess + refine directness with geo / environment / land-type topic gates.
     *
     * @return array{
     *     directness: string,
     *     verification_label: string,
     *     score: float,
     *     reasons: list<string>,
     *     factor_coverage: float,
     *     sense_coverage: bool,
     *     entity_matched: bool,
     *     topic_matched: bool
     * }
     */
    public function assess(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult|string $titleOrResult,
        ?string $abstract = null,
        ?string $doi = null,
        ?string $extraText = null,
        ?ScientificSearchResult $result = null,
    ): array {
        if ($titleOrResult instanceof ScientificSearchResult) {
            $result = $titleOrResult;
            $title = $result->title;
            $abstract = $abstract ?? $result->abstract;
            $doi = $doi ?? $result->doi;
        } else {
            $title = $titleOrResult;
        }

        $base = $this->directnessAssessor->assess($plan, $title, $abstract, $doi, $extraText);
        $refined = $this->refine($plan, $base, $title, $abstract, $extraText, $result);
        $refined['verification_label'] = $this->toVerificationLabel($refined['directness']);

        return $refined;
    }

    /**
     * Map internal directness constants to verification-layer labels.
     */
    public function toVerificationLabel(string $directness): string
    {
        return match ($directness) {
            ScientificEvidenceDirectnessAssessor::DIRECT => self::LABEL_DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED => self::LABEL_SUPPORTED,
            ScientificEvidenceDirectnessAssessor::RELATED,
            ScientificEvidenceDirectnessAssessor::BACKGROUND => self::LABEL_RELATED,
            ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH => self::LABEL_GEOGRAPHIC_MISMATCH,
            ScientificEvidenceDirectnessAssessor::IRRELEVANT => self::LABEL_IRRELEVANT,
            default => self::LABEL_RELATED,
        };
    }

    public function isPrimaryCitationEligible(string $directness): bool
    {
        return in_array($directness, [
            ScientificEvidenceDirectnessAssessor::DIRECT,
            ScientificEvidenceDirectnessAssessor::SUPPORTING,
            ScientificEvidenceDirectnessAssessor::SUPPORTED,
        ], true);
    }

    /**
     * @param  array{
     *     directness: string,
     *     score: float,
     *     reasons: list<string>,
     *     factor_coverage: float,
     *     sense_coverage: bool,
     *     entity_matched: bool,
     *     topic_matched: bool
     * }  $base
     * @return array{
     *     directness: string,
     *     score: float,
     *     reasons: list<string>,
     *     factor_coverage: float,
     *     sense_coverage: bool,
     *     entity_matched: bool,
     *     topic_matched: bool
     * }
     */
    private function refine(
        KnowledgeQueryPlan $plan,
        array $base,
        string $title,
        ?string $abstract,
        ?string $extraText,
        ?ScientificSearchResult $result,
    ): array {
        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $title,
            $abstract,
            $extraText,
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        // Geo: study-country mismatch → GEOGRAPHIC_MISMATCH (publisher ≠ study country).
        if ($this->hasGeographicMismatch($plan, $haystack, $result)) {
            return [
                'directness' => ScientificEvidenceDirectnessAssessor::GEOGRAPHIC_MISMATCH,
                'score' => 0.0,
                'reasons' => array_values(array_unique(array_merge(
                    $base['reasons'] ?? [],
                    ['geographic_study_country_mismatch'],
                ))),
                'factor_coverage' => (float) ($base['factor_coverage'] ?? 0.0),
                'sense_coverage' => (bool) ($base['sense_coverage'] ?? false),
                'entity_matched' => (bool) ($base['entity_matched'] ?? false),
                'topic_matched' => (bool) ($base['topic_matched'] ?? false),
            ];
        }

        // Topic: land-types must not become DIRECT via ornamental/greenhouse papers unless asked.
        if ($this->isLandTypesQuestion($plan)
            && $this->hasUnaskedLandTypeOfftopicMarkers($plan, $haystack)
            && ($base['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::DIRECT) {
            return [
                'directness' => ScientificEvidenceDirectnessAssessor::RELATED,
                'score' => min(6.0, (float) ($base['score'] ?? 0.0)),
                'reasons' => array_values(array_unique(array_merge(
                    $base['reasons'] ?? [],
                    ['land_types_offtopic_crop_or_environment'],
                ))),
                'factor_coverage' => (float) ($base['factor_coverage'] ?? 0.0),
                'sense_coverage' => (bool) ($base['sense_coverage'] ?? false),
                'entity_matched' => (bool) ($base['entity_matched'] ?? false),
                'topic_matched' => false,
            ];
        }

        // Environment: open-field vs greenhouse/polyhouse/hydroponics mismatch demotes DIRECT.
        if (($base['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::DIRECT
            && $this->hasEnvironmentMismatch($plan, $haystack)) {
            return [
                'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'score' => min(16.0, (float) ($base['score'] ?? 0.0)),
                'reasons' => array_values(array_unique(array_merge(
                    $base['reasons'] ?? [],
                    ['production_environment_mismatch'],
                ))),
                'factor_coverage' => (float) ($base['factor_coverage'] ?? 0.0),
                'sense_coverage' => (bool) ($base['sense_coverage'] ?? false),
                'entity_matched' => (bool) ($base['entity_matched'] ?? false),
                'topic_matched' => (bool) ($base['topic_matched'] ?? false),
            ];
        }

        // Semantic / citation scores cannot force or preserve DIRECT alone.
        if (($base['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::DIRECT
            && $this->directnessDrivenOnlyBySemanticOrCitations($base, $result)) {
            return [
                'directness' => ScientificEvidenceDirectnessAssessor::SUPPORTING,
                'score' => min(14.0, (float) ($base['score'] ?? 0.0)),
                'reasons' => array_values(array_unique(array_merge(
                    $base['reasons'] ?? [],
                    ['semantic_or_citations_cannot_force_direct'],
                ))),
                'factor_coverage' => (float) ($base['factor_coverage'] ?? 0.0),
                'sense_coverage' => (bool) ($base['sense_coverage'] ?? false),
                'entity_matched' => (bool) ($base['entity_matched'] ?? false),
                'topic_matched' => (bool) ($base['topic_matched'] ?? false),
            ];
        }

        // Normalize BACKGROUND → RELATED for verification-facing reasons (keep BACKGROUND constant too).
        if (($base['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::BACKGROUND) {
            $base['directness'] = ScientificEvidenceDirectnessAssessor::RELATED;
            $base['reasons'] = array_values(array_unique(array_merge(
                $base['reasons'] ?? [],
                ['mapped_background_to_related'],
            )));
        }

        return $base;
    }

    private function hasGeographicMismatch(
        KnowledgeQueryPlan $plan,
        string $haystack,
        ?ScientificSearchResult $result,
    ): bool {
        $asked = $this->askedLocationCanonical($plan);
        if ($asked === null) {
            return false;
        }

        $askedIso = AgriculturalEntityCatalog::locationToIsoCountryCode($asked);
        $studyCountries = $this->extractStudyCountries($result, $haystack);
        if ($studyCountries === []) {
            // No study-country signal → do not invent a mismatch from publisher alone.
            return false;
        }

        foreach ($studyCountries as $codeOrName) {
            $normalized = mb_strtolower(trim($codeOrName));
            if ($normalized === '') {
                continue;
            }
            if ($askedIso !== null && ($normalized === $askedIso || $normalized === mb_strtolower($asked))) {
                return false;
            }
            $mapped = AgriculturalEntityCatalog::locationToIsoCountryCode($normalized);
            if ($mapped !== null && $askedIso !== null && $mapped === $askedIso) {
                return false;
            }
            if (mb_strtolower($asked) === $normalized) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function extractStudyCountries(?ScientificSearchResult $result, string $haystack): array
    {
        $found = [];
        if ($result !== null) {
            $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];
            if (is_array($meta['countries_of_study'] ?? null)) {
                foreach ($meta['countries_of_study'] as $country) {
                    $found[] = (string) $country;
                }
            }
            $raw = is_array($result->rawMetadata['consensus'] ?? null) ? $result->rawMetadata['consensus'] : [];
            if (is_array($raw['countries_of_study'] ?? null)) {
                foreach ($raw['countries_of_study'] as $country) {
                    $found[] = (string) $country;
                }
            }
            // OpenAlex institution country_code (study geography proxy) — never publisher alone.
            $oa = is_array($result->rawMetadata['openalex'] ?? null) ? $result->rawMetadata['openalex'] : [];
            $authorships = is_array($oa['authorships'] ?? null) ? $oa['authorships'] : [];
            foreach ($authorships as $authorship) {
                if (! is_array($authorship)) {
                    continue;
                }
                $institutions = is_array($authorship['institutions'] ?? null) ? $authorship['institutions'] : [];
                foreach ($institutions as $institution) {
                    if (! is_array($institution)) {
                        continue;
                    }
                    $code = trim((string) ($institution['country_code'] ?? ''));
                    if ($code !== '') {
                        $found[] = strtolower($code);
                    }
                }
            }
        }

        foreach (AgriculturalEntityCatalog::locationAliases() as $alias => $canonical) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower($alias))
                || AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower($canonical))) {
                $iso = AgriculturalEntityCatalog::locationToIsoCountryCode($canonical);
                $found[] = $iso ?? mb_strtolower($canonical);
            }
        }

        return array_values(array_unique(array_filter($found)));
    }

    private function askedLocationCanonical(KnowledgeQueryPlan $plan): ?string
    {
        $fromQuery = trim((string) ($plan->normalizedQuery->location ?? ''));
        $raw = $fromQuery !== ''
            ? $fromQuery
            : trim((string) ($plan->normalizedQuery->constraints['location'] ?? ''));
        if ($raw === '') {
            return null;
        }

        $lower = mb_strtolower($raw);
        foreach (AgriculturalEntityCatalog::locationAliases() as $alias => $canonical) {
            if ($lower === mb_strtolower($alias) || $lower === mb_strtolower($canonical)) {
                return $canonical;
            }
        }

        return $raw;
    }

    private function isLandTypesQuestion(KnowledgeQueryPlan $plan): bool
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense === 'land_classification') {
            return true;
        }

        $topics = is_array($plan->normalizedQuery->constraints['scientific_topics'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_topics']
            : [];
        foreach ($topics as $topic) {
            if (preg_match('/land\s*types?|soil\s*classification|land\s*classification|أنواع\s*الأراضي/u', mb_strtolower((string) $topic)) === 1) {
                return true;
            }
        }

        $hay = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));

        return preg_match(
            '/land\s*types?|soil\s*classification|land\s*classification|أنواع\s*(?:ال)?أراضي|تصنيف\s*(?:ال)?أراضي|أنواع\s*التربة/u',
            $hay,
        ) === 1;
    }

    private function hasUnaskedLandTypeOfftopicMarkers(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        $question = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));

        foreach (self::LAND_TYPE_OFFTOPIC_MARKERS as $marker) {
            if (! AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                && mb_strpos($haystack, $marker) === false) {
                continue;
            }
            // Allowed when the user explicitly asked about that crop/system.
            if (AgriculturalEntityCatalog::containsTerm($question, $marker)
                || mb_strpos($question, $marker) !== false) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function hasEnvironmentMismatch(KnowledgeQueryPlan $plan, string $haystack): bool
    {
        $question = mb_strtolower(trim(
            $plan->normalizedQuery->originalQuestion.' '.$plan->normalizedQuery->normalizedQuestion
        ));
        $productionSystem = trim((string) ($plan->normalizedQuery->constraints['production_system'] ?? ''));

        $askedProtected = in_array($productionSystem, ['hydroponics', 'greenhouse'], true)
            || $this->containsAnyMarker($question, self::PROTECTED_ENV_MARKERS);
        $askedOpenField = $productionSystem === 'open_field'
            || $this->containsAnyMarker($question, self::OPEN_FIELD_MARKERS);

        $evidenceProtected = $this->containsAnyMarker($haystack, self::PROTECTED_ENV_MARKERS);
        $evidenceOpenField = $this->containsAnyMarker($haystack, self::OPEN_FIELD_MARKERS);

        if ($askedOpenField && ! $askedProtected && $evidenceProtected && ! $evidenceOpenField) {
            return true;
        }
        if ($askedProtected && ! $askedOpenField && $evidenceOpenField && ! $evidenceProtected) {
            return true;
        }
        // Explicit production_system constraint vs opposing evidence.
        if ($productionSystem === 'open_field' && $evidenceProtected && ! $evidenceOpenField) {
            return true;
        }
        if (in_array($productionSystem, ['greenhouse', 'hydroponics'], true)
            && $evidenceOpenField && ! $evidenceProtected) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $markers
     */
    private function containsAnyMarker(string $haystack, array $markers): bool
    {
        foreach ($markers as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                || mb_strpos($haystack, $marker) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $base
     */
    private function directnessDrivenOnlyBySemanticOrCitations(array $base, ?ScientificSearchResult $result): bool
    {
        $reasons = is_array($base['reasons'] ?? null) ? $base['reasons'] : [];
        $hasStructural = in_array('entity_topic_sense_aligned', $reasons, true)
            || in_array('germination_evidence_preferred', $reasons, true)
            || in_array('growth_evidence_preferred', $reasons, true);
        if ($hasStructural) {
            return false;
        }

        if ($result === null) {
            return false;
        }

        $meta = is_array($result->relevanceMetadata) ? $result->relevanceMetadata : [];

        return isset($meta['semantic_score']) || isset($meta['citation_count']);
    }
}
