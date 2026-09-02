<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;
use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceValidationExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;
use App\Services\Agriculture\ScientificSourceValidator;

/**
 * Stage 5 evidence-bound answer synthesis.
 *
 * Does not browse the Internet, invent evidence, or bypass Stage 4 validation.
 */
class AnswerComposer
{
    public function __construct(
        private ScientificSourceValidator $sourceValidator,
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
            fn (ScientificEvidenceItem $item): bool => $this->isSynthesizable($item),
        ));

        if ($usable === []) {
            return $this->insufficientReport(
                status: 'no_validated_evidence',
                reason: 'no_validated_evidence',
                language: $language,
                query: $query,
                plan: $plan,
                rejectedCount: $validationReport->rejectedCount,
            );
        }

        $citations = $this->buildCitations($usable);
        $claims = $this->buildClaims($usable);
        $conflicts = $this->buildConflicts($usable, $language);
        $keyFindings = $this->buildKeyFindings($claims, $language);
        $limitations = $this->buildLimitations($validationReport, $usable, $language);
        $uncertainty = $this->resolveUncertainty($validationReport, $usable, $conflicts, $language);
        $confidence = $this->overallConfidence($claims, $validationReport);
        $evidenceReferences = array_map(
            static fn (ScientificEvidenceItem $item): array => [
                'evidence_id' => $item->evidenceId,
                'source_id' => $item->sourceId,
                'publication_title' => $item->publicationTitle,
                'claim_relationship' => $item->claimRelationship,
                'validation_status' => $item->validationStatus,
                'has_conflict' => $item->hasConflict,
            ],
            $usable,
        );

        $conciseSummary = $this->buildConciseSummary($keyFindings, $uncertainty, $language);
        $detailedExplanation = $this->buildDetailedExplanation($usable, $citations, $conflicts, $language);
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
                'subject_entity' => $plan->subjectEntity,
                'validation_status' => $validationReport->status,
                'evidence_sufficient' => $validationReport->evidenceSufficient,
                'internet_first' => $plan->isInternetFirst(),
                'synthesized_at' => now()->toIso8601String(),
            ],
            observability: [
                'usable_evidence_count' => count($usable),
                'claims_generated' => count($claims),
                'citations_mapped' => count($citations),
                'conflicts_detected' => count($conflicts),
                'independent_search' => false,
                'validation_bypassed' => false,
            ],
        );
    }

    private function isSynthesizable(ScientificEvidenceItem $item): bool
    {
        if (! $item->isUsable()) {
            return false;
        }

        return in_array($item->claimRelationship, [
            ClaimEvidenceRelationship::SUPPORTED,
            ClaimEvidenceRelationship::PARTIALLY_SUPPORTED,
            ClaimEvidenceRelationship::CONFLICTING,
        ], true);
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
    private function buildClaims(array $items): array
    {
        $claims = [];
        foreach ($items as $item) {
            if ($item->evidenceText === null || trim($item->evidenceText) === '') {
                continue;
            }

            $limitations = [];
            if ($item->claimRelationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED) {
                $limitations[] = 'partial_evidence_support';
            }
            if ($item->hasConflict) {
                $limitations[] = 'conflicting_evidence';
            }

            $claims[] = new ResearchAnswerClaim(
                claimId: 'claim-'.$item->evidenceId,
                claimText: trim($item->evidenceText),
                evidenceIds: [$item->evidenceId],
                sourceIds: [$item->sourceId],
                validationStatus: $item->validationStatus,
                claimRelationship: $item->claimRelationship,
                confidence: $item->confidence,
                numericalValues: $this->extractNumericalValues($item->evidenceText),
                limitations: $limitations,
                conditions: is_array($item->conditions) ? json_encode($item->conditions) : null,
            );
        }

        return $claims;
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
     * @return list<string>
     */
    private function buildLimitations(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        string $language,
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
     */
    private function resolveUncertainty(
        EvidenceValidationExecutionReport $validationReport,
        array $usable,
        array $conflicts,
        string $language,
    ): ?string {
        if (! $validationReport->evidenceSufficient) {
            return $language === 'ar'
                ? 'الأدلة العلمية المتاحة غير كافية لإعطاء نتيجة مؤكدة.'
                : 'Available scientific evidence is insufficient for a definitive conclusion.';
        }

        if ($conflicts !== []) {
            return $language === 'ar'
                ? 'توجد أدلة متعارضة؛ لا ينبغي افتراض قيمة أو استنتاج واحد universal.'
                : 'Conflicting evidence exists; a single universal value or conclusion should not be assumed.';
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
    ): string {
        $parts = [];

        foreach ($items as $index => $item) {
            if ($item->evidenceText === null || trim($item->evidenceText) === '') {
                continue;
            }

            $citationLabel = $this->citationLabel($item, $language);
            $parts[] = ($index + 1).'. '.$item->evidenceText.($citationLabel !== '' ? ' ('.$citationLabel.')' : '');
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

    /** @param  array<string, mixed>  $plan */
    private function insufficientReport(
        string $status,
        string $reason,
        string $language,
        string $query,
        KnowledgeQueryPlan $plan,
        int $rejectedCount = 0,
    ): AnswerSynthesisExecutionReport {
        $message = $language === 'ar'
            ? 'الأدلة العلمية المتاحة غير كافية لإعطاء نتيجة مؤكدة.'
            : 'Available scientific evidence is insufficient for a definitive conclusion.';

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
