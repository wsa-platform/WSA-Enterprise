<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\AgriculturalEntityCatalog;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Search\ScientificEvidenceDirectnessAssessor;
use App\Services\Agriculture\Research\Search\ScientificEvidenceRelevanceGate;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Stage 5 evidence-bound answer synthesis.
 *
 * Does not browse the Internet, invent evidence, or bypass Stage 4 validation.
 * Background-only leftovers and off-topic abstract sentences are not answers.
 */
class AnswerComposer
{
    public function __construct(
        private ScientificSourceValidator $sourceValidator,
        private ScientificEvidenceRelevanceGate $relevanceGate,
        private ScientificEvidenceDirectnessAssessor $directnessAssessor,
    ) {}

    public function compose(
        KnowledgeQueryPlan $plan,
        EvidenceValidationExecutionReport $validationReport,
    ): AnswerSynthesisExecutionReport {
        $language = $plan->normalizedQuery->language;
        $query = $plan->normalizedQuery->originalQuestion;

        if (in_array($validationReport->status, ['needs_clarification', 'no_search_results'], true)) {
            return $this->insufficientReport(
                status: $validationReport->status,
                reason: $validationReport->status,
                language: $language,
                query: $query,
                plan: $plan,
            );
        }

        $usable = array_values(array_filter(
            $validationReport->validatedEvidence,
            fn (ScientificEvidenceItem $item): bool => $this->isSynthesizable($item, $plan),
        ));

        if ($usable === []) {
            return $this->insufficientReport(
                status: 'no_validated_evidence',
                reason: 'no_relevant_validated_evidence',
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
            );
        }

        $sufficiency = $this->assessSynthesisSufficiency($usable, $validationReport, $plan);
        if (! $sufficiency['sufficient']) {
            return $this->insufficientReport(
                status: 'insufficient_evidence',
                reason: $sufficiency['reason'],
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
            );
        }

        $citations = $this->buildCitations($usable);
        $claims = $this->buildClaims($usable, $plan);
        if ($claims === []) {
            return $this->insufficientReport(
                status: 'insufficient_evidence',
                reason: 'no_grounded_claims',
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
            );
        }

        $conflicts = $this->buildConflicts($usable, $language);
        $keyFindings = $this->buildKeyFindings($claims, $language);
        $limitations = $this->buildLimitations($validationReport, $usable, $language, $sufficiency);
        $uncertainty = $this->resolveUncertainty($validationReport, $usable, $conflicts, $language, $sufficiency);
        $confidence = $this->overallConfidence($claims, $validationReport);
        $evidenceReferences = array_map(
            static fn (ScientificEvidenceItem $item): array => [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'publication_title' => $item->publicationTitle,
                'claim_relationship' => $item->claimRelationship,
                'validation_status' => $item->validationStatus,
                'has_conflict' => $item->hasConflict,
                'evidence_directness' => $item->qualityFactors['evidence_directness']
                    ?? ($item->sourceAttribution['evidence_directness'] ?? null),
            ],
            $usable,
        );

        $conciseSummary = $this->buildConciseSummary($keyFindings, $uncertainty, $language);
        $detailedExplanation = $this->buildDetailedExplanation($usable, $citations, $conflicts, $language, $plan);
        $answer = $this->buildAnswer($conciseSummary, $detailedExplanation, $uncertainty, $language);

        $status = match (true) {
            $conflicts !== [] && count($claims) <= count($conflicts) => 'synthesis_completed_with_conflicts',
            $conflicts !== [] => 'synthesis_completed_with_partial_conflicts',
            count($claims) < count($usable) => 'synthesis_completed_partial',
            default => 'synthesis_completed',
        };

        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: $answer,
            conciseSummary: $conciseSummary,
            detailedExplanation: $detailedExplanation,
            keyFindings: $keyFindings,
            claims: $claims,
            citations: $citations,
            evidenceReferences: $evidenceReferences,
            confidence: $confidence,
            limitations: $limitations,
            uncertainty: $uncertainty,
            conflicts: $conflicts,
            language: $language,
            researchMetadata: [
                'query' => $query,
                'normalized_query' => $plan->normalizedQuery->normalizedQuestion,
                'research_intent' => $plan->researchIntent,
                'agricultural_domain' => $plan->agriculturalDomain,
                'scientific_sense' => $plan->normalizedQuery->constraints['scientific_sense'] ?? null,
                'scientific_intent_qualifier' => $plan->normalizedQuery->constraints['scientific_intent_qualifier'] ?? null,
                'subject_entity' => $plan->subjectEntity,
                'validation_status' => $validationReport->status,
                'evidence_sufficient' => $validationReport->evidenceSufficient && $sufficiency['sufficient'],
                'internet_first' => $plan->isInternetFirst(),
                'synthesized_at' => now()->toIso8601String(),
                'direct_evidence_count' => $sufficiency['direct_count'],
                'supporting_evidence_count' => $sufficiency['supporting_count'],
            ],
            observability: [
                'usable_evidence_count' => count($usable),
                'claims_generated' => count($claims),
                'citations_mapped' => count($citations),
                'conflicts_detected' => count($conflicts),
                'independent_search' => false,
                'validation_bypassed' => false,
                'evidence_directness_filter' => true,
            ],
        );
    }

    private function isSynthesizable(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): bool
    {
        if (! $item->isUsable()) {
            return false;
        }

        if (! in_array($item->claimRelationship, [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ClaimEvidenceRelationship::CONFLICTING,
        ], true)) {
            return false;
        }

        if (! $this->relevanceGate->isRelevant(
            $plan,
            $item->publicationTitle,
            $item->evidenceText,
            $item->doi,
            $item->claimTopic !== '' ? $item->claimTopic : null,
        )) {
            return false;
        }

        $directness = $this->resolveDirectness($item, $plan);
        $strict = $this->requiresStrictGrounding($plan);
        if ($strict && in_array($directness, [
            ScientificEvidenceDirectnessAssessor::IRRELEVANT,
            ScientificEvidenceDirectnessAssessor::BACKGROUND,
        ], true)) {
            return false;
        }
        if ($directness === ScientificEvidenceDirectnessAssessor::IRRELEVANT) {
            return false;
        }

        return true;
    }

    private function requiresStrictGrounding(KnowledgeQueryPlan $plan): bool
    {
        $hasEntity = $plan->normalizedQuery->cropId !== null
            || $plan->normalizedQuery->scientificName !== null
            || ((is_array($plan->subjectEntity) ? ($plan->subjectEntity['type'] ?? null) : null) === 'crop');
        $factors = $plan->normalizedQuery->constraints['scientific_factors'] ?? [];

        return $hasEntity && is_array($factors) && $factors !== [];
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @return array{
     *     sufficient: bool,
     *     partial: bool,
     *     reason: string,
     *     direct_count: int,
     *     supporting_count: int
     * }
     */
    private function assessSynthesisSufficiency(
        array $usable,
        EvidenceValidationExecutionReport $validationReport,
        KnowledgeQueryPlan $plan,
    ): array {
        $directCount = 0;
        $supportingCount = 0;
        foreach ($usable as $item) {
            $directness = (string) ($item->qualityFactors['evidence_directness']
                ?? $item->sourceAttribution['evidence_directness']
                ?? ScientificEvidenceDirectnessAssessor::SUPPORTING);
            if ($directness === ScientificEvidenceDirectnessAssessor::DIRECT) {
                $directCount++;
            } elseif ($directness === ScientificEvidenceDirectnessAssessor::SUPPORTING) {
                $supportingCount++;
            }
        }

        if ($directCount >= 1) {
            return [
                'sufficient' => true,
                'partial' => false,
                'reason' => 'direct_evidence_present',
                'direct_count' => $directCount,
                'supporting_count' => $supportingCount,
            ];
        }

        if (! $this->requiresStrictGrounding($plan) && $usable !== []) {
            return [
                'sufficient' => true,
                'partial' => $directCount === 0,
                'reason' => 'general_query_usable_evidence',
                'direct_count' => $directCount,
                'supporting_count' => max($supportingCount, count($usable)),
            ];
        }

        $strongSupporting = count(array_filter(
            $usable,
            static fn (ScientificEvidenceItem $item): bool => $item->claimRelationship === ClaimEvidenceRelationship::SUPPORTED
                || $item->confidence >= 0.55,
        ));

        if ($supportingCount >= 1 && $strongSupporting >= 1) {
            return [
                'sufficient' => true,
                'partial' => true,
                'reason' => 'supporting_evidence_only',
                'direct_count' => $directCount,
                'supporting_count' => $supportingCount,
            ];
        }

        if ($supportingCount >= 2 || (count($usable) >= 2 && $validationReport->evidenceSufficient)) {
            return [
                'sufficient' => true,
                'partial' => true,
                'reason' => 'multiple_supporting_evidence',
                'direct_count' => $directCount,
                'supporting_count' => $supportingCount,
            ];
        }

        return [
            'sufficient' => false,
            'partial' => false,
            'reason' => 'background_or_weak_evidence_only',
            'direct_count' => $directCount,
            'supporting_count' => $supportingCount,
        ];
    }

    private function resolveDirectness(ScientificEvidenceItem $item, KnowledgeQueryPlan $plan): string
    {
        $stored = $item->qualityFactors['evidence_directness']
            ?? $item->sourceAttribution['evidence_directness']
            ?? null;
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return $this->directnessAssessor->assess(
            $plan,
            $item->publicationTitle,
            $item->evidenceText,
            $item->doi,
        )['directness'];
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ResearchAnswerCitation>
     */
    private function buildCitations(array $items): array
    {
        $citations = [];
        foreach ($items as $item) {
            $reference = $this->citationFromEvidence($item);
            if ($reference === null) {
                continue;
            }
            $citations[] = $reference;
        }

        return $citations;
    }

    private function citationFromEvidence(ScientificEvidenceItem $item): ?ResearchAnswerCitation
    {
        $reference = [
            'source_type' => $item->sourceType ?? 'supporting_verified',
            'organization' => $item->institution ?? ($item->sourceAttribution['organization'] ?? ''),
            'title' => $item->publicationTitle,
        ];

        if ($item->url !== null && $item->url !== '') {
            $reference['url'] = $item->url;
        }

        if ($item->doi !== null && $item->doi !== '') {
            $reference['doi'] = $item->doi;
        }

        if (! $this->sourceValidator->isVerifiedSource($reference)) {
            return null;
        }

        return new ResearchAnswerCitation(
            citationId: 'cite-'.$item->evidenceId,
            sourceId: $item->sourceId,
            evidenceId: $item->evidenceId,
            title: $item->publicationTitle,
            authors: $item->authors,
            organization: is_string($reference['organization']) ? $reference['organization'] : null,
            journal: $item->journal,
            doi: $item->doi,
            url: $item->url,
            publicationYear: $item->publicationYear,
            sourceType: $item->sourceType,
        );
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<ResearchAnswerClaim>
     */
    private function buildClaims(array $items, KnowledgeQueryPlan $plan): array
    {
        $claims = [];
        foreach ($items as $item) {
            if ($item->evidenceText === null || trim($item->evidenceText) === '') {
                continue;
            }

            $groundedText = $this->selectGroundedSnippet($item->evidenceText, $plan, $item->publicationTitle);
            if ($groundedText === '') {
                continue;
            }

            $limitations = [];
            if ($item->claimRelationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED) {
                $limitations[] = 'partial_evidence_support';
            }
            if ($item->hasConflict) {
                $limitations[] = 'conflicting_evidence';
            }
            $directness = $this->resolveDirectness($item, $plan);
            if ($directness === ScientificEvidenceDirectnessAssessor::SUPPORTING) {
                $limitations[] = 'supporting_not_direct_evidence';
            }

            $claims[] = new ResearchAnswerClaim(
                claimId: 'claim-'.$item->evidenceId,
                claimText: $groundedText,
                evidenceIds: [$item->evidenceId],
                sourceIds: [$item->sourceId],
                validationStatus: $item->validationStatus,
                claimRelationship: $item->claimRelationship,
                confidence: $item->confidence,
                numericalValues: $this->extractNumericalValues($groundedText),
                limitations: $limitations,
                conditions: is_array($item->conditions) ? json_encode($item->conditions) : null,
            );
        }

        return $claims;
    }

    private function selectGroundedSnippet(string $text, KnowledgeQueryPlan $plan, string $title): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $needles = $this->groundingNeedles($plan);
        $sentences = preg_split('/(?<=[.!?؟])\s+/u', $text) ?: [$text];
        $best = '';
        $bestScore = -1.0;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }
            $hay = mb_strtolower($sentence);
            $score = 0.0;
            foreach ($needles as $needle) {
                if ($needle !== '' && AgriculturalEntityCatalog::containsTerm($hay, $needle)) {
                    $score += mb_strlen($needle);
                }
            }
            // Prefer sentences that also align with title topic words.
            foreach ($this->terms($title) as $titleTerm) {
                if (str_contains($hay, $titleTerm)) {
                    $score += 1.0;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $sentence;
            }
        }

        // If no sentence overlaps structured intent, refuse off-topic abstract lead-ins
        // only for crop+topic questions; general queries may use the best available sentence.
        if ($bestScore <= 0.0 && $needles !== [] && $this->requiresStrictGrounding($plan)) {
            return '';
        }

        return $best !== '' ? $best : $this->firstSentence($text);
    }

    /**
     * @return list<string>
     */
    private function groundingNeedles(KnowledgeQueryPlan $plan): array
    {
        $needles = [];
        $topics = $plan->normalizedQuery->constraints['scientific_topics'] ?? [];
        if (is_array($topics)) {
            foreach ($topics as $topic) {
                $needles[] = mb_strtolower(trim((string) $topic));
            }
        }
        $sense = trim((string) ($plan->normalizedQuery->constraints['scientific_sense'] ?? ''));
        if ($sense !== '') {
            foreach (AgriculturalEntityCatalog::senseQueryTerms($sense) as $term) {
                $needles[] = mb_strtolower(trim($term));
            }
        }
        if ($plan->normalizedQuery->scientificName !== null) {
            $needles[] = mb_strtolower($plan->normalizedQuery->scientificName);
        }
        foreach (AgriculturalEntityCatalog::englishTermsForIntent($plan->researchIntent) as $term) {
            if (! in_array($term, ['agriculture', 'farming'], true)) {
                $needles[] = mb_strtolower($term);
            }
        }

        return array_values(array_unique(array_filter($needles)));
    }

    /**
     * @return list<string>
     */
    private function extractNumericalValues(string $text): array
    {
        preg_match_all('/\b\d+(?:[.,]\d+)?(?:\s*(?:%|kg|ha|mm|cm|m|l|ml|°c|ph|ppm|mg|g|tons?|days?|weeks?|months?))?\b/iu', $text, $matches);

        $values = [];
        foreach ($matches[0] ?? [] as $match) {
            $clean = trim((string) $match);
            if ($clean !== '' && ! in_array($clean, $values, true)) {
                $values[] = $clean;
            }
        }

        return $values;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @return list<array<string, mixed>>
     */
    private function buildConflicts(array $items, string $language): array
    {
        $conflicts = [];
        foreach ($items as $item) {
            if (! $item->hasConflict && $item->claimRelationship !== ClaimEvidenceRelationship::CONFLICTING) {
                continue;
            }

            $conflicts[] = [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'publication_title' => $item->publicationTitle,
                'evidence_text' => $item->evidenceText,
                'publication_year' => $item->publicationYear,
                'conditions' => $item->conditions,
                'numerical_values' => $this->extractNumericalValues((string) $item->evidenceText),
                'message' => $language === 'ar'
                    ? 'توجد أدلة علمية متعارضة لهذا الموضوع؛ القيمة أو الاستنتاج قد يختلف حسب المصدر والظروف.'
                    : 'Conflicting scientific evidence exists for this topic; values or conclusions may differ by source and conditions.',
            ];
        }

        return $conflicts;
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @return list<string>
     */
    private function buildKeyFindings(array $claims, string $language): array
    {
        $findings = [];
        foreach ($claims as $claim) {
            if ($claim->claimRelationship === ClaimEvidenceRelationship::CONFLICTING) {
                continue;
            }
            $sentence = $this->firstSentence($claim->claimText);
            if ($sentence !== '' && ! in_array($sentence, $findings, true)) {
                $findings[] = $sentence;
            }
        }

        if ($findings === [] && $claims !== []) {
            $findings[] = $language === 'ar'
                ? 'تتوفر أدلة علمية محدودة أو متعارضة؛ راجع التفاصيل والمصادر.'
                : 'Limited or conflicting scientific evidence is available; review details and sources.';
        }

        return array_slice($findings, 0, 5);
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  array<string, mixed>  $sufficiency
     * @return list<string>
     */
    private function buildLimitations(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        string $language,
        array $sufficiency,
    ): array {
        $limitations = [];

        if ($validationReport->rejectedCount > 0) {
            $limitations[] = $language === 'ar'
                ? sprintf('تم استبعاد %d مصدرًا لعدم اجتياز التحقق العلمي.', $validationReport->rejectedCount)
                : sprintf('%d source(s) were excluded for failing scientific validation.', $validationReport->rejectedCount);
        }

        $partialCount = count(array_filter(
            $usable,
            fn (ScientificEvidenceItem $item): bool => $item->claimRelationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
        ));
        if ($partialCount > 0) {
            $limitations[] = $language === 'ar'
                ? sprintf('%d مصدرًا يقدم دعمًا جزئيًا فقط.', $partialCount)
                : sprintf('%d source(s) provide partial support only.', $partialCount);
        }

        if (($sufficiency['partial'] ?? false) === true) {
            $limitations[] = $language === 'ar'
                ? 'الأدلة داعمة جزئيًا وليست مباشرة بالكامل للسؤال.'
                : 'Evidence is supporting rather than fully direct for the question.';
        }

        if ($validationReport->conflictingCount > 0) {
            $limitations[] = $language === 'ar'
                ? 'توجد تعارضات بين بعض المصادر العلمية المعتمدة.'
                : 'Some validated scientific sources disagree.';
        }

        return $limitations;
    }

    /**
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  list<array<string, mixed>>  $conflicts
     * @param  array<string, mixed>  $sufficiency
     */
    private function resolveUncertainty(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        array $conflicts,
        string $language,
        array $sufficiency,
    ): ?string {
        if (! $validationReport->evidenceSufficient || ! ($sufficiency['sufficient'] ?? false)) {
            return $language === 'ar'
                ? 'الأدلة العلمية المتاحة غير كافية لإعطاء نتيجة مؤكدة.'
                : 'Available scientific evidence is insufficient for a definitive conclusion.';
        }

        if ($conflicts !== []) {
            return $language === 'ar'
                ? 'توجد أدلة متعارضة؛ لا ينبغي افتراض قيمة أو استنتاج واحد universal.'
                : 'Conflicting evidence exists; a single universal value or conclusion should not be assumed.';
        }

        if (($sufficiency['direct_count'] ?? 0) === 0 && ($sufficiency['supporting_count'] ?? 0) >= 1) {
            return $language === 'ar'
                ? 'الأدلة المتاحة داعمة جزئيًا؛ قد لا تحدد قيمة مثلى أو استنتاجًا مباشرًا بمفردها.'
                : 'Available evidence is supporting only; it may not alone establish an optimal value or direct conclusion.';
        }

        $supported = count(array_filter(
            $usable,
            fn (ScientificEvidenceItem $item): bool => $item->claimRelationship === ClaimEvidenceRelationship::SUPPORTED,
        ));

        if ($supported === 1 && count($usable) === 1) {
            return $language === 'ar'
                ? 'الاستنتاج يعتمد على مصدر علمي واحد معتمد؛ قد تتطلب التطبيقات العملية مصادر إضافية.'
                : 'The conclusion relies on a single validated source; practical applications may require additional evidence.';
        }

        return null;
    }

    /**
     * @param  list<ResearchAnswerClaim>  $claims
     */
    private function overallConfidence(array $claims, EvidenceValidationExecutionReport $validationReport): float
    {
        if ($claims === []) {
            return 0.0;
        }

        $total = array_sum(array_map(fn (ResearchAnswerClaim $claim): float => $claim->confidence, $claims));

        return round(min(0.95, $total / count($claims)), 3);
    }

    /**
     * @param  list<string>  $keyFindings
     */
    private function buildConciseSummary(array $keyFindings, ?string $uncertainty, string $language): string
    {
        if ($keyFindings === []) {
            return $uncertainty ?? ($language === 'ar'
                ? 'لا تتوفر أدلة علمية معتمدة كافية.'
                : 'Insufficient validated scientific evidence is available.');
        }

        return $keyFindings[0];
    }

    /**
     * @param  list<ScientificEvidenceItem>  $items
     * @param  list<ResearchAnswerCitation>  $citations
     * @param  list<array<string, mixed>>  $conflicts
     */
    private function buildDetailedExplanation(
        array $items,
        array $citations,
        array $conflicts,
        string $language,
        KnowledgeQueryPlan $plan,
    ): string {
        $parts = [];

        foreach ($items as $index => $item) {
            if ($item->evidenceText === null || trim($item->evidenceText) === '') {
                continue;
            }

            $snippet = $this->selectGroundedSnippet($item->evidenceText, $plan, $item->publicationTitle);
            if ($snippet === '') {
                continue;
            }

            $citationLabel = $this->citationLabel($item, $language);
            $parts[] = ($index + 1).'. '.$snippet.($citationLabel !== '' ? ' ('.$citationLabel.')' : '');
        }

        if ($conflicts !== []) {
            $parts[] = '';
            $parts[] = $language === 'ar'
                ? 'ملاحظة حول التعارض: '.($conflicts[0]['message'] ?? '')
                : 'Conflict note: '.($conflicts[0]['message'] ?? '');
        }

        return implode("\n", $parts);
    }

    private function buildAnswer(string $conciseSummary, string $detailedExplanation, ?string $uncertainty, string $language): string
    {
        $sections = [$conciseSummary, '', $detailedExplanation];
        if ($uncertainty !== null) {
            $sections[] = '';
            $sections[] = ($language === 'ar' ? 'درجة اليقين: ' : 'Uncertainty: ').$uncertainty;
        }

        return trim(implode("\n", array_filter($sections, fn (?string $part): bool => $part !== null)));
    }

    private function citationLabel(ScientificEvidenceItem $item, string $language): string
    {
        $org = $item->institution ?? ($item->sourceAttribution['organization'] ?? '');
        $year = $item->publicationYear !== null ? (string) $item->publicationYear : '';

        if ($org === '' && $year === '') {
            return '';
        }

        return trim($org.($year !== '' ? ', '.$year : ''));
    }

    private function firstSentence(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?؟])(\s|$)/u', $text, $matches)) {
            return trim($matches[1]);
        }

        return mb_strlen($text) > 240 ? mb_substr($text, 0, 237).'...' : $text;
    }

    /** @return list<string> */
    private function terms(string $text): array
    {
        $normalized = mb_strtolower(trim($text));
        $parts = preg_split('/\s+/u', $normalized) ?: [];

        return array_values(array_filter($parts, static fn (string $part): bool => mb_strlen($part) >= 4));
    }

    private function insufficientReport(
        string $status,
        string $reason,
        string $language,
        string $query,
        KnowledgeQueryPlan $plan,
        int $rejectedCount = 0,
    ): AnswerSynthesisExecutionReport {
        $message = $language === 'ar'
            ? ($reason === 'no_relevant_validated_evidence' || $reason === 'background_or_weak_evidence_only'
                ? 'الأدلة العلمية المتاحة غير ذات صلة كافية أو غير كافية لإعطاء نتيجة مؤكدة.'
                : 'الأدلة العلمية المتاحة غير كافية لإعطاء نتيجة مؤكدة.')
            : ($reason === 'no_relevant_validated_evidence' || $reason === 'background_or_weak_evidence_only'
                ? 'Available scientific evidence is not sufficiently relevant for a definitive conclusion.'
                : 'Available scientific evidence is insufficient for a definitive conclusion.');

        return new AnswerSynthesisExecutionReport(
            status: $status,
            performed: true,
            answer: $message,
            conciseSummary: $message,
            detailedExplanation: $message,
            keyFindings: [],
            claims: [],
            citations: [],
            evidenceReferences: [],
            confidence: 0.0,
            limitations: $rejectedCount > 0
                ? [($language === 'ar'
                    ? sprintf('تم رفض %d مصدرًا أثناء التحقق.', $rejectedCount)
                    : sprintf('%d source(s) were rejected during validation.', $rejectedCount))]
                : [],
            uncertainty: $message,
            conflicts: [],
            language: $language,
            researchMetadata: [
                'query' => $query,
                'research_intent' => $plan->researchIntent,
                'agricultural_domain' => $plan->agriculturalDomain,
                'failure_reason' => $reason,
                'internet_first' => $plan->isInternetFirst(),
            ],
            observability: [
                'usable_evidence_count' => 0,
                'claims_generated' => 0,
                'citations_mapped' => 0,
                'conflicts_detected' => 0,
                'independent_search' => false,
                'validation_bypassed' => false,
                'failure_reason' => $reason,
            ],
        );
    }
}
