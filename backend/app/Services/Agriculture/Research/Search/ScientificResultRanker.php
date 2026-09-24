<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Deterministic relevance ranking — not scientific validity (Stage 4 / Phase 4).
 *
 * Query-aware scores: entity / topic / intent / sense / directness / quality metadata.
 * Diversity prefers provider/journal/institution — never country.
 *
 * Ownership (Phase-4 Unit B):
 * - relevanceScore = ordering preference only (not confidence, not verification, not sufficiency).
 * - rejected_by_relevance_gate = preliminary ranking exclusion / soft demotion marker for ordering;
 *   Stage-4 validation remains authoritative for scientific usability and R5 save eligibility.
 * - Geographic / required-evidence adjustments are ranking preferences after topical gating;
 *   they never rescue topically rejected results and never decide Library-save.
 */
class ScientificResultRanker
{
    /** Country-level inventory: prefer national content scope (+15% of post-topical score). */
    private const NATIONAL_SCOPE_BONUS_RATIO = 0.15;

    /** Country-level inventory: demote regional content scope (−10% of post-topical score). */
    private const REGIONAL_SCOPE_PENALTY_RATIO = 0.10;

    public const DOCUMENT_GEO_SCOPE_NATIONAL = 'national';

    public const DOCUMENT_GEO_SCOPE_REGIONAL = 'regional';

    public const DOCUMENT_GEO_SCOPE_UNKNOWN = 'unknown';

    public function __construct(
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
        private ScientificStatisticalClaimAligner $statisticalClaimAligner,
    ) {}

    /**
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function rank(string $searchQuery, array $results, ?KnowledgeQueryPlan $plan = null): array
    {
        $queryTerms = $this->terms($searchQuery);
        $ranked = [];

        foreach ($results as $result) {
            $score = $this->scoreResult($result, $queryTerms);
            $metadata = [
                'ranking_basis' => 'query_relevance_directness_quality',
                'not_scientific_validation' => true,
                // Phase-4: explicit score role — ordering only.
                'score_role' => 'ranking_order',
            ];

            if ($plan !== null) {
                $observation = ScientificStructuredObservation::fromResult($result);
                if (ScientificEvidenceModality::isDirectStatistical($result)
                    && $observation !== null
                    && $observation->isComplete()) {
                    $alignment = $this->statisticalClaimAligner->assess($plan, $observation);
                    $metadata['evidence_modality'] = ScientificEvidenceModality::DIRECT_STATISTICAL;
                    $metadata['statistical_claim_aligned'] = $alignment['relevant'];
                    $metadata['entity_matched'] = ! in_array('entity', $alignment['mismatches'], true);
                    $metadata['topic_matched'] = ! in_array('property', $alignment['mismatches'], true);
                    $metadata['sense_matched'] = $alignment['relevant'];
                    $metadata['context_matched'] = $alignment['relevant'];
                    $metadata['context_adequate'] = $alignment['relevant'];
                    $metadata['relevance_gate'] = $alignment['relevant'];
                    $metadata['directness_reasons'] = $alignment['mismatches'];
                    $metadata['factor_coverage'] = $alignment['relevant'] ? 1.0 : 0.0;
                    if ($alignment['relevant']) {
                        $score += 80.0;
                        $metadata['evidence_directness'] = ScientificEvidenceDirectnessAssessor::DIRECT;
                        $metadata['ranking_class'] = 0;
                    } else {
                        $metadata['rejected_by_relevance_gate'] = true;
                        $metadata['rejection_reasons'] = $alignment['mismatches'];
                        $metadata['evidence_directness'] = ScientificEvidenceDirectnessAssessor::IRRELEVANT;
                        $metadata['ranking_class'] = 4;
                        $score *= 0.05;
                    }
                } else {
                    $assessment = $this->relevanceGate->assess(
                        $plan,
                        $result->title,
                        $result->abstract,
                        $result->doi,
                        $this->extraTextFromResult($result),
                    );
                    $directness = $this->directnessAssessor->assess(
                        $plan,
                        $result->title,
                        $result->abstract,
                        $result->doi,
                        $this->extraTextFromResult($result),
                    );

                    $score += $assessment['score'];
                    $score += $directness['score'];
                    $score += $this->qualityMetadataBonus($result);
                    $score += $this->topicDirectnessBonus($assessment, $directness);
                    $score += $this->germinationIntentRankingAdjust($plan, $result);
                    $score += $this->requiredEvidenceRankingAdjust($plan, $result, $directness);

                    $metadata['entity_matched'] = $assessment['entity_matched'];
                    $metadata['topic_matched'] = $assessment['topic_matched'];
                    $metadata['sense_matched'] = $assessment['sense_matched'] ?? false;
                    $metadata['context_matched'] = $assessment['context_matched'] ?? false;
                    $metadata['context_adequate'] = $assessment['context_adequate'] ?? false;
                    $metadata['relevance_gate'] = $assessment['relevant'];
                    $metadata['evidence_directness'] = $directness['directness'];
                    $metadata['ranking_class'] = ScientificEvidenceDirectnessAssessor::rankingClass(
                        (string) $directness['directness'],
                    );
                    $metadata['directness_reasons'] = $directness['reasons'];
                    $metadata['factor_coverage'] = $directness['factor_coverage'];
                    $metadata['evidence_modality'] = ScientificEvidenceModality::fromResult($result);

                    $topicallyRejected = ! $assessment['relevant']
                        || $directness['directness'] === ScientificEvidenceDirectnessAssessor::IRRELEVANT;
                    if ($topicallyRejected) {
                        $metadata['rejected_by_relevance_gate'] = true;
                        $metadata['rejection_reasons'] = $assessment['rejection_reasons'] !== []
                            ? $assessment['rejection_reasons']
                            : $directness['reasons'];
                        $score *= 0.05;
                    }

                    // Geographic scope only after topical gate; never rescues irrelevant docs.
                    $geo = $this->geographicScopeRankingAdjust($plan, $result, ! $topicallyRejected, $score);
                    $score += $geo['delta'];
                    $metadata['document_geo_scope'] = $geo['document_scope'];
                    $metadata['query_geo_level'] = $geo['query_level'];
                    $metadata['geo_scope_delta'] = $geo['delta'];
                }
            }

            if ($this->isPeerReviewNoise($result)) {
                $metadata['peer_review_noise'] = true;
                $score *= 0.08;
            }

            $ranked[] = $result->withRelevanceScore($score, $metadata);
        }

        usort($ranked, function (ScientificSearchResult $a, ScientificSearchResult $b): int {
            $aClass = ScientificEvidenceDirectnessAssessor::rankingClass(
                (string) ($a->relevanceMetadata['evidence_directness'] ?? ''),
            );
            $bClass = ScientificEvidenceDirectnessAssessor::rankingClass(
                (string) ($b->relevanceMetadata['evidence_directness'] ?? ''),
            );
            if ($aClass !== $bClass) {
                return $aClass <=> $bClass;
            }

            $scoreCompare = ($b->relevanceScore ?? 0.0) <=> ($a->relevanceScore ?? 0.0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp($a->title, $b->title);
        });

        return $ranked;
    }

    /**
     * Drop results marked for ranking exclusion when a plan is available.
     * Also drop OpenAlex "Peer Review #N" wrappers when primary literature remains.
     *
     * Preliminary ranking-pipeline filter only — not Stage-4 verification and not R5 save authority.
     *
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function filterRelevant(array $results): array
    {
        $filtered = array_values(array_filter(
            $results,
            static fn (ScientificSearchResult $result): bool => ! ($result->relevanceMetadata['rejected_by_relevance_gate'] ?? false),
        ));

        $withoutPeerNoise = array_values(array_filter(
            $filtered,
            static fn (ScientificSearchResult $result): bool => ! ($result->relevanceMetadata['peer_review_noise'] ?? false),
        ));

        // Prefer primary literature; keep peer-review wrappers only if nothing else remains.
        return $withoutPeerNoise !== [] ? $withoutPeerNoise : $filtered;
    }

    /**
     * Mild diversity across provider / journal / institution when scores are similar.
     * Country is never used.
     *
     * @param  list<ScientificSearchResult>  $results
     * @return list<ScientificSearchResult>
     */
    public function diversifyByProviderJournalInstitution(array $results): array
    {
        if (count($results) <= 2) {
            return $results;
        }

        $selected = [];
        $seenProviders = [];
        $seenJournals = [];
        $seenInstitutions = [];
        $deferred = [];

        foreach ($results as $index => $result) {
            $topScore = $results[0]->relevanceScore ?? 0.0;
            $score = $result->relevanceScore ?? 0.0;
            $similarQuality = $topScore <= 0.0 || abs($topScore - $score) <= max(8.0, $topScore * 0.12);

            $provider = $result->sourceKey;
            $journal = mb_strtolower(trim((string) ($result->journal ?? '')));
            $institution = mb_strtolower(trim((string) ($this->institutionFromResult($result) ?? '')));

            $providerDup = isset($seenProviders[$provider]) && $seenProviders[$provider] >= 2;
            $journalDup = $journal !== '' && isset($seenJournals[$journal]);
            $institutionDup = $institution !== '' && isset($seenInstitutions[$institution]);

            if ($index === 0 || ! $similarQuality || (! $providerDup && ! $journalDup && ! $institutionDup)) {
                $selected[] = $result;
                $seenProviders[$provider] = ($seenProviders[$provider] ?? 0) + 1;
                if ($journal !== '') {
                    $seenJournals[$journal] = true;
                }
                if ($institution !== '') {
                    $seenInstitutions[$institution] = true;
                }

                continue;
            }

            $deferred[] = $result;
        }

        return array_values(array_merge($selected, $deferred));
    }

    /**
     * @param  list<string>  $queryTerms
     */
    private function scoreResult(ScientificSearchResult $result, array $queryTerms): float
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $result->journal,
            implode(' ', $result->authors),
        ])));

        if ($haystack === '') {
            return 0.0;
        }

        $score = 0.0;
        foreach ($queryTerms as $term) {
            if ($term === '') {
                continue;
            }
            if (str_contains($haystack, $term)) {
                $score += mb_strlen($term);
            }
        }

        $normalizedTitle = mb_strtolower(trim($result->title));
        $normalizedQuery = mb_strtolower(trim(implode(' ', $queryTerms)));
        if ($normalizedTitle !== '' && $normalizedQuery !== '' && str_contains($normalizedTitle, $normalizedQuery)) {
            $score += 20.0;
        }

        if ($result->publicationYear !== null && $result->publicationYear >= (int) date('Y') - 10) {
            $score += 1.0;
        }

        if (count($result->foundBySources) > 1) {
            $score += 2.0;
        }

        return $score;
    }

    private function qualityMetadataBonus(ScientificSearchResult $result): float
    {
        $bonus = 0.0;
        if ($result->doi !== null && trim($result->doi) !== '') {
            $bonus += 2.0;
        }
        if ($result->journal !== null && trim($result->journal) !== '') {
            $bonus += 1.5;
        }
        if ($result->publicationYear !== null) {
            $bonus += 0.5;
        }
        if (count($result->authors) > 0) {
            $bonus += 0.5;
        }

        return $bonus;
    }

    /**
     * Boost query-topic relevance and directness; never uses country.
     *
     * @param  array<string, mixed>  $assessment
     * @param  array<string, mixed>  $directness
     */
    private function topicDirectnessBonus(array $assessment, array $directness): float
    {
        $bonus = 0.0;
        if (($assessment['entity_matched'] ?? false) && ($assessment['topic_matched'] ?? false)) {
            $bonus += 8.0;
        }
        if (($assessment['sense_matched'] ?? false) || ($assessment['context_matched'] ?? false)) {
            $bonus += 4.0;
        }

        $directnessLabel = (string) ($directness['directness'] ?? '');
        $bonus += match ($directnessLabel) {
            ScientificEvidenceDirectnessAssessor::DIRECT => 12.0,
            ScientificEvidenceDirectnessAssessor::SUPPORTING => 4.0,
            ScientificEvidenceDirectnessAssessor::BACKGROUND => -2.0,
            default => 0.0,
        };

        return $bonus;
    }

    /**
     * Germination questions: boost seed-germination/temperature evidence; demote essential-oil primary.
     * Never applies country preference.
     */
    private function germinationIntentRankingAdjust(KnowledgeQueryPlan $plan, ScientificSearchResult $result): float
    {
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        $factors = is_array($plan->normalizedQuery->constraints['scientific_factors'] ?? null)
            ? $plan->normalizedQuery->constraints['scientific_factors']
            : [];
        $isGermination = $sense === 'seed_germination' || in_array('germination', $factors, true);
        if (! $isGermination) {
            return 0.0;
        }

        $questionHay = mb_strtolower(trim(implode(' ', array_filter([
            $plan->normalizedQuery->normalizedQuestion,
            $plan->normalizedQuery->originalQuestion,
        ]))));
        if (AgriculturalEntityCatalog::userAskedAboutOils($questionHay)) {
            return 0.0;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $this->extraTextFromResult($result),
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        $adjust = 0.0;
        $hasGerm = false;
        foreach (AgriculturalEntityCatalog::germinationEvidenceSignals() as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, mb_strtolower(trim($signal)))) {
                $hasGerm = true;
                break;
            }
        }
        if ($hasGerm) {
            $adjust += 10.0;
        }

        $hasOil = false;
        foreach (AgriculturalEntityCatalog::essentialOilPrimaryMarkers() as $marker) {
            $normalized = mb_strtolower(trim($marker));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                || mb_strpos($haystack, $normalized) !== false
            )) {
                $hasOil = true;
                break;
            }
        }
        if ($hasOil) {
            $adjust -= $hasGerm ? 18.0 : 28.0;
        }

        return $adjust;
    }

    /**
     * Boost results that contain the required evidence content for the question type.
     *
     * @param  array<string, mixed>  $directness
     */
    private function requiredEvidenceRankingAdjust(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        array $directness,
    ): float {
        $required = trim((string) ($plan->normalizedQuery->constraints['required_evidence_type'] ?? ''));
        if ($required === '' || $required === 'topic_aligned_scientific_claim') {
            return 0.0;
        }

        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $this->extraTextFromResult($result),
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));

        $adjust = 0.0;
        foreach (AgriculturalEntityCatalog::requiredEvidenceQueryTerms($required) as $term) {
            $normalized = mb_strtolower(trim($term));
            if ($normalized !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                || mb_strpos($haystack, $normalized) !== false
            )) {
                $adjust += 4.0;
                break;
            }
        }

        if ($required === 'classification_or_types_inventory') {
            // Ranking preference only: lexical inventory signals. Catalog land helpers are optional
            // (Phase-6 Catalog WIP must not be required to stage Phase-4 ranking).
            $hasInventory = $this->haystackHasInventoryClassificationSignals($haystack);
            if ($hasInventory) {
                $adjust += 10.0;
            } else {
                // Lexical "classification" without inventory must not rank as if DIRECT.
                $adjust -= 6.0;
            }
            foreach ($this->landClassificationAnswerabilitySignals() as $signal) {
                $normalized = mb_strtolower(trim($signal));
                if ($normalized !== '' && (
                    AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                    || mb_strpos($haystack, $normalized) !== false
                )) {
                    $adjust += $hasInventory ? 8.0 : 2.0;
                    break;
                }
            }
            foreach ($this->landClassificationOfftopicMarkers() as $marker) {
                $normalized = mb_strtolower(trim($marker));
                if ($normalized !== '' && (
                    AgriculturalEntityCatalog::containsTerm($haystack, $normalized)
                    || mb_strpos($haystack, $normalized) !== false
                )) {
                    $adjust -= $hasInventory ? 4.0 : 14.0;
                    break;
                }
            }
            if ($this->isClassificationMethodologyOnlyWithoutInventory($haystack)) {
                $adjust -= 16.0;
            }
        }

        if (($directness['directness'] ?? '') === ScientificEvidenceDirectnessAssessor::DIRECT) {
            $adjust += 6.0;
        }

        return $adjust;
    }

    /**
     * Inventory/classification only: prefer matching geographic content scope.
     * Country-level queries: national > regional. Regional queries: regional > national.
     * Applied after topical relevance; affiliation/publisher geo alone never counts.
     *
     * @return array{
     *     delta: float,
     *     document_scope: string,
     *     query_level: ?string
     * }
     */
    private function geographicScopeRankingAdjust(
        KnowledgeQueryPlan $plan,
        ScientificSearchResult $result,
        bool $topicallyRelevant,
        float $currentScore,
    ): array {
        $neutral = [
            'delta' => 0.0,
            'document_scope' => self::DOCUMENT_GEO_SCOPE_UNKNOWN,
            'query_level' => null,
        ];

        if (! $topicallyRelevant || ! $this->isInventoryClassificationQuery($plan)) {
            return $neutral;
        }

        $location = $this->planLocation($plan);
        $queryLevel = $this->queryGeographicLevel($plan, $location);
        if ($queryLevel === null || $location === '') {
            return $neutral;
        }

        $contentHaystack = $this->contentScopeHaystack($result);
        $documentScope = $this->detectDocumentGeographicScope($contentHaystack, $queryLevel, $location);

        $delta = 0.0;
        if ($queryLevel === 'country') {
            if ($documentScope === self::DOCUMENT_GEO_SCOPE_NATIONAL) {
                $delta = $currentScore * self::NATIONAL_SCOPE_BONUS_RATIO;
            } elseif ($documentScope === self::DOCUMENT_GEO_SCOPE_REGIONAL) {
                $delta = -($currentScore * self::REGIONAL_SCOPE_PENALTY_RATIO);
            }
        } elseif ($queryLevel === 'regional') {
            if ($documentScope === self::DOCUMENT_GEO_SCOPE_REGIONAL) {
                $delta = $currentScore * self::NATIONAL_SCOPE_BONUS_RATIO;
            } elseif ($documentScope === self::DOCUMENT_GEO_SCOPE_NATIONAL) {
                $delta = -($currentScore * self::REGIONAL_SCOPE_PENALTY_RATIO);
            }
        }

        return [
            'delta' => $delta,
            'document_scope' => $documentScope,
            'query_level' => $queryLevel,
        ];
    }

    private function isInventoryClassificationQuery(KnowledgeQueryPlan $plan): bool
    {
        $constraints = $plan->normalizedQuery->constraints;
        $required = trim((string) ($constraints['required_evidence_type'] ?? ''));
        $questionType = trim((string) ($constraints['question_type'] ?? ''));
        $sense = trim((string) ($constraints['scientific_sense'] ?? ''));
        $intent = trim((string) ($plan->researchIntent ?? ''));
        $subtopic = trim((string) ($plan->normalizedQuery->subtopic ?? ''));

        return $required === 'classification_or_types_inventory'
            || $questionType === 'classification'
            || $sense === 'land_classification'
            || $intent === 'land_classification'
            || $subtopic === 'classification_inventory';
    }

    private function planLocation(KnowledgeQueryPlan $plan): string
    {
        $fromQuery = trim((string) ($plan->normalizedQuery->location ?? ''));
        if ($fromQuery !== '') {
            return $fromQuery;
        }

        return trim((string) ($plan->normalizedQuery->constraints['location'] ?? ''));
    }

    /**
     * @return 'country'|'regional'|null
     */
    private function queryGeographicLevel(KnowledgeQueryPlan $plan, string $location): ?string
    {
        if ($location === '') {
            return null;
        }

        if ($this->isCountryLevelLocation($location)) {
            return 'country';
        }

        // Non-empty location that is not a known country ⇒ subnational / regional query.
        return 'regional';
    }

    private function isCountryLevelLocation(string $location): bool
    {
        $normalized = mb_strtolower(trim($location));
        if ($normalized === '') {
            return false;
        }

        if (AgriculturalEntityCatalog::locationToIsoCountryCode($location) !== null) {
            return true;
        }

        foreach (AgriculturalEntityCatalog::locationAliases() as $alias => $canonical) {
            if ($normalized === mb_strtolower($alias) || $normalized === mb_strtolower($canonical)) {
                return true;
            }
        }

        // Generic country labels beyond catalog ISO map (no country-specific ranking branches).
        return in_array($normalized, $this->genericCountryLabels(), true);
    }

    /**
     * Well-known sovereign country labels for geo-level detection only.
     *
     * @return list<string>
     */
    private function genericCountryLabels(): array
    {
        return [
            'france', 'brazil', 'canada', 'india', 'germany', 'italy', 'spain', 'portugal',
            'china', 'japan', 'australia', 'mexico', 'argentina', 'chile', 'peru',
            'kenya', 'ethiopia', 'nigeria', 'ghana', 'pakistan', 'bangladesh',
            'indonesia', 'thailand', 'vietnam', 'philippines', 'united states', 'usa',
            'united kingdom', 'uk', 'poland', 'romania', 'greece', 'netherlands',
            'belgium', 'sweden', 'norway', 'denmark', 'finland', 'ireland',
            'egypt', 'saudi arabia', 'turkey', 'türkiye', 'turkiye', 'morocco',
            'libya', 'sudan', 'tunisia', 'algeria', 'jordan', 'united arab emirates',
            'uae', 'iraq', 'syria', 'lebanon', 'yemen', 'oman', 'kuwait', 'qatar',
            'bahrain', 'iran', 'afghanistan', 'south africa', 'tanzania', 'uganda',
            'russia', 'ukraine', 'kazakhstan', 'uzbekistan', 'new zealand',
            'colombia', 'venezuela', 'ecuador', 'bolivia', 'paraguay', 'uruguay',
            'cuba', 'haiti', 'dominican republic', 'guatemala', 'honduras',
            'nicaragua', 'costa rica', 'panama', 'senegal', 'mali', 'niger',
            'chad', 'cameroon', 'angola', 'mozambique', 'madagascar', 'malawi',
            'zambia', 'zimbabwe', 'botswana', 'namibia', 'rwanda', 'burundi',
            'somalia', 'djibouti', 'eritrea', 'mauritania', 'gambia', 'benin',
            'togo', 'sierra leone', 'liberia', 'ivory coast', "côte d'ivoire",
            'burkina faso', 'central african republic', 'congo', 'dr congo',
            'south korea', 'north korea', 'mongolia', 'nepal', 'sri lanka',
            'myanmar', 'cambodia', 'laos', 'malaysia', 'singapore', 'brunei',
            'papua new guinea', 'fiji', 'solomon islands', 'vanuatu', 'samoa',
            'israel', 'palestine', 'cyprus', 'malta', 'iceland', 'luxembourg',
            'switzerland', 'austria', 'czech republic', 'czechia', 'slovakia',
            'hungary', 'slovenia', 'croatia', 'serbia', 'bosnia and herzegovina',
            'montenegro', 'albania', 'north macedonia', 'bulgaria', 'moldova',
            'belarus', 'georgia', 'armenia', 'azerbaijan', 'turkmenistan',
            'tajikistan', 'kyrgyzstan', 'mongolia',
        ];
    }

    /**
     * Title + abstract + concept keywords only — never authorship affiliation.
     */
    private function contentScopeHaystack(ScientificSearchResult $result): string
    {
        return mb_strtolower(trim(implode(' ', array_filter([
            $result->title,
            $result->abstract,
            $this->extraTextFromResult($result),
        ], static fn ($part): bool => is_string($part) && trim($part) !== ''))));
    }

    /**
     * Content geographic scope for inventory ranking.
     * Country mention alone is insufficient for NATIONAL; affiliation never consulted.
     *
     * @param  'country'|'regional'  $queryLevel
     */
    private function detectDocumentGeographicScope(string $haystack, string $queryLevel, string $location): string
    {
        if ($haystack === '') {
            return self::DOCUMENT_GEO_SCOPE_UNKNOWN;
        }

        $locationLower = mb_strtolower(trim($location));

        if ($queryLevel === 'regional') {
            if ($this->haystackMentionsLocationToken($haystack, $locationLower)) {
                return self::DOCUMENT_GEO_SCOPE_REGIONAL;
            }
            if ($this->hasNationalScopeMarkers($haystack)) {
                return self::DOCUMENT_GEO_SCOPE_NATIONAL;
            }

            return self::DOCUMENT_GEO_SCOPE_UNKNOWN;
        }

        // Country-level inventory query.
        if ($this->hasRegionalScopeMarkers($haystack) || $this->hasSubnationalBeforeCountry($haystack, $locationLower)) {
            return self::DOCUMENT_GEO_SCOPE_REGIONAL;
        }

        if ($this->hasNationalScopeMarkers($haystack) || $this->hasCountryFramedNationalInventory($haystack, $locationLower)) {
            return self::DOCUMENT_GEO_SCOPE_NATIONAL;
        }

        return self::DOCUMENT_GEO_SCOPE_UNKNOWN;
    }

    private function hasRegionalScopeMarkers(string $haystack): bool
    {
        $markers = [
            'governorate', 'province', 'district', 'county', 'prefecture', 'municipality',
            'wilaya', 'oblast', 'canton', 'département', 'department of', 'região',
            'eyalet', 'regional study', 'local scale', 'watershed', 'catchment',
            'peninsula', 'oasis', 'delta of', 'river basin',
            'محافظة', 'ولايه', 'ولاية', 'إقليم', 'اقليم', 'منطقة', 'المنطقة',
            'gouvernorat', 'province de', 'région de', 'département de',
            'ilçe', 'vilayet', 'bölge',
        ];

        foreach ($markers as $marker) {
            if ($marker !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                || mb_strpos($haystack, $marker) !== false
            )) {
                return true;
            }
        }

        // Cardinal / relative + place framing marks subnational study areas (generic).
        if (preg_match(
            '/\b(?:south|north|east|west|southern|northern|eastern|western|upper|lower|central)\s+[a-z\p{L}]{3,40}\b/u',
            $haystack,
        ) === 1) {
            return true;
        }

        return false;
    }

    private function hasNationalScopeMarkers(string $haystack): bool
    {
        $markers = [
            'national', 'nationwide', 'nation-wide', 'country-wide', 'countrywide',
            'national scale', 'national-level', 'national level', 'national inventory',
            'national soil', 'national land', 'soil map of', 'land map of',
            'across the country', 'throughout the country', 'whole country',
            'republic-wide', 'federal inventory',
            'وطني', 'القومي', 'قومي', 'على مستوى الجمهورية', 'خريطة التربة',
            'à l\'échelle nationale', 'echelle nationale', 'carte des sols',
            'ulusal', 'ülke çapında', 'ulke capinda', 'ulusal ölçek',
        ];

        foreach ($markers as $marker) {
            if ($marker !== '' && (
                AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                || mb_strpos($haystack, $marker) !== false
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Country framed as the study area for inventory/classification — not a single bare mention.
     */
    private function hasCountryFramedNationalInventory(string $haystack, string $countryLocation): bool
    {
        $country = $this->canonicalCountryNeedle($countryLocation);
        if ($country === '') {
            return false;
        }

        $countryQuoted = preg_quote($country, '/');
        $inventory = '(?:soil|land|agricultural\s+land| الأراضي|الاراضي|تربة|الأراضي)';
        $class = '(?:types?|classes?|classification|map|inventory|taxonomy|kinds?|أنواع|انواع|تصنيف|خريطة)';

        // e.g. "soil types of Morocco", "land classification in Brazil", "Soil Map of France"
        if (preg_match(
            '/'.$inventory.'.{0,48}'.$class.'.{0,32}\b'.$countryQuoted.'\b/u',
            $haystack,
        ) === 1) {
            return true;
        }
        if (preg_match(
            '/\b'.$countryQuoted.'\b.{0,32}'.$inventory.'.{0,48}'.$class.'/u',
            $haystack,
        ) === 1) {
            return true;
        }
        if (preg_match(
            '/(?:soil|land)\s+map\s+of\s+'.$countryQuoted.'\b/u',
            $haystack,
        ) === 1) {
            return true;
        }

        // Adjectival demonym + inventory without regional markers (e.g. egyptian / french soils).
        $demonym = $this->countryDemonym($country);
        if ($demonym !== '' && preg_match(
            '/\b'.$demonym.'\b.{0,40}(?:soil|land).{0,40}(?:types?|classes?|classification)/u',
            $haystack,
        ) === 1) {
            return true;
        }
        if ($demonym !== '' && preg_match(
            '/(?:soil|land).{0,40}(?:types?|classes?|classification).{0,40}\b'.$demonym.'\b/u',
            $haystack,
        ) === 1) {
            return true;
        }

        return false;
    }

    /**
     * "Provence, France" / "Punjab, India" — subnational place before country.
     */
    private function hasSubnationalBeforeCountry(string $haystack, string $countryLocation): bool
    {
        $country = $this->canonicalCountryNeedle($countryLocation);
        if ($country === '') {
            return false;
        }

        $countryQuoted = preg_quote($country, '/');

        return preg_match(
            '/\b([a-z\p{L}][a-z\p{L}\s\-]{2,40}),\s*'.$countryQuoted.'\b/u',
            $haystack,
            $matches,
        ) === 1
            && isset($matches[1])
            && ! $this->isCountryLevelLocation(trim($matches[1]));
    }

    private function haystackMentionsLocationToken(string $haystack, string $location): bool
    {
        $location = mb_strtolower(trim($location));
        if ($location === '' || $haystack === '') {
            return false;
        }

        if (method_exists(AgriculturalEntityCatalog::class, 'haystackMentionsLocation')
            && AgriculturalEntityCatalog::haystackMentionsLocation($haystack, $location)) {
            return true;
        }

        return AgriculturalEntityCatalog::containsTerm($haystack, $location)
            || mb_strpos($haystack, $location) !== false;
    }

    /**
     * Phase-4-safe inventory signals for ranking preference (not scientific verification).
     *
     * Prefer Catalog helpers when present; otherwise use local lexical fallbacks so ranking
     * does not require Phase-6 Catalog WIP to be staged.
     */
    private function haystackHasInventoryClassificationSignals(string $haystack): bool
    {
        if (method_exists(AgriculturalEntityCatalog::class, 'hasLandTypeInventoryContent')) {
            return AgriculturalEntityCatalog::hasLandTypeInventoryContent($haystack);
        }

        foreach ([
            'soil type', 'soil types', 'land type', 'land types', 'land class', 'land classes',
            'soil classification', 'land classification', 'soil map', 'land capability',
            'inventory of', 'nationwide inventory', 'soil taxonomy',
        ] as $signal) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $signal)
                || mb_strpos($haystack, $signal) !== false) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function landClassificationAnswerabilitySignals(): array
    {
        if (method_exists(AgriculturalEntityCatalog::class, 'landClassificationAnswerabilitySignals')) {
            return AgriculturalEntityCatalog::landClassificationAnswerabilitySignals();
        }

        return [
            'soil type', 'soil types', 'land type', 'land types', 'land class',
            'soil classification', 'land classification', 'land capability',
        ];
    }

    /** @return list<string> */
    private function landClassificationOfftopicMarkers(): array
    {
        if (method_exists(AgriculturalEntityCatalog::class, 'landClassificationOfftopicMarkers')) {
            return AgriculturalEntityCatalog::landClassificationOfftopicMarkers();
        }

        return [
            'greenhouse', 'hydroponic', 'hydroponics', 'gerbera', 'ornamental',
            'machine learning', 'remote sensing', 'neural network',
        ];
    }

    private function isClassificationMethodologyOnlyWithoutInventory(string $haystack): bool
    {
        if (method_exists(AgriculturalEntityCatalog::class, 'isClassificationMethodologyOnlyWithoutInventory')) {
            return AgriculturalEntityCatalog::isClassificationMethodologyOnlyWithoutInventory($haystack);
        }

        $methodMarkers = ['machine learning', 'deep learning', 'neural network', 'random forest', 'cnn', 'gis'];
        $hasMethod = false;
        foreach ($methodMarkers as $marker) {
            if (AgriculturalEntityCatalog::containsTerm($haystack, $marker)
                || mb_strpos($haystack, $marker) !== false) {
                $hasMethod = true;
                break;
            }
        }

        return $hasMethod && ! $this->haystackHasInventoryClassificationSignals($haystack);
    }

    private function canonicalCountryNeedle(string $location): string
    {
        $normalized = mb_strtolower(trim($location));
        if ($normalized === '') {
            return '';
        }

        foreach (AgriculturalEntityCatalog::locationAliases() as $alias => $canonical) {
            if ($normalized === mb_strtolower($alias) || $normalized === mb_strtolower($canonical)) {
                return mb_strtolower($canonical);
            }
        }

        return $normalized;
    }

    private function countryDemonym(string $countryNeedle): string
    {
        return match ($countryNeedle) {
            'egypt' => 'egyptian',
            'saudi arabia' => 'saudi',
            'turkey', 'türkiye', 'turkiye' => 'turkish',
            'morocco' => 'moroccan',
            'france' => 'french',
            'brazil' => 'brazilian',
            'india' => 'indian',
            'canada' => 'canadian',
            'libya' => 'libyan',
            'sudan' => 'sudanese',
            'tunisia' => 'tunisian',
            'algeria' => 'algerian',
            'jordan' => 'jordanian',
            default => '',
        };
    }

    /**
     * OpenAlex often indexes "Peer Review #N of …" wrappers that dilute ranking.
     */
    private function isPeerReviewNoise(ScientificSearchResult $result): bool
    {
        $title = trim($result->title);
        if ($title === '') {
            return false;
        }

        return preg_match('/^\s*peer\s*reviews?\s*#?\s*\d+/iu', $title) === 1
            || preg_match('/\bpeer\s*reviews?\s*#\s*\d+/iu', $title) === 1;
    }

    private function institutionFromResult(ScientificSearchResult $result): ?string
    {
        $raw = $result->rawMetadata ?? [];
        $openalex = is_array($raw['openalex'] ?? null) ? $raw['openalex'] : [];
        $authorships = is_array($openalex['authorships'] ?? null) ? $openalex['authorships'] : [];
        foreach ($authorships as $authorship) {
            if (! is_array($authorship)) {
                continue;
            }
            $institutions = is_array($authorship['institutions'] ?? null) ? $authorship['institutions'] : [];
            foreach ($institutions as $institution) {
                if (is_array($institution) && isset($institution['display_name'])) {
                    $name = trim((string) $institution['display_name']);
                    if ($name !== '') {
                        return $name;
                    }
                }
            }
        }

        return null;
    }

    /** @return list<string> */
    private function terms(string $query): array
    {
        $normalized = mb_strtolower(trim($query));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 3));
    }

    /**
     * Keywords/concepts/metadata as secondary text after title+abstract.
     */
    private function extraTextFromResult(ScientificSearchResult $result): ?string
    {
        $parts = [];
        $raw = $result->rawMetadata ?? [];

        $openalex = is_array($raw['openalex'] ?? null) ? $raw['openalex'] : [];
        foreach (is_array($openalex['concepts'] ?? null) ? $openalex['concepts'] : [] as $concept) {
            if (is_array($concept) && isset($concept['display_name'])) {
                $parts[] = (string) $concept['display_name'];
            }
        }
        foreach (is_array($openalex['keywords'] ?? null) ? $openalex['keywords'] : [] as $keyword) {
            if (is_array($keyword) && isset($keyword['display_name'])) {
                $parts[] = (string) $keyword['display_name'];
            } elseif (is_string($keyword)) {
                $parts[] = $keyword;
            }
        }

        $crossref = is_array($raw['crossref'] ?? null) ? $raw['crossref'] : [];
        foreach (is_array($crossref['subject'] ?? null) ? $crossref['subject'] : [] as $subject) {
            if (is_string($subject)) {
                $parts[] = $subject;
            }
        }

        $joined = trim(implode(' ', $parts));

        return $joined !== '' ? $joined : null;
    }
}
