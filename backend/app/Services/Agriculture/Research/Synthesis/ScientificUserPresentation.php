<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsage;
use App\Services\Agriculture\Research\Search\Contract\ScientificSourceUsageStatus;
use App\Services\Agriculture\Research\Search\ScientificSearchExecutionReport;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * User-facing Scientific Presentation.
 *
 * Internal synthesis/diagnostics stay on the scientific model.
 * This object is the only contract Home/Crop/Viewer should render.
 */
final class ScientificUserPresentation
{
    public const HUMAN_ANSWERED = 'answered';

    public const HUMAN_INSUFFICIENT = 'insufficient';

    /** Final-answer display policy. Distinct from candidate PRESENTATION_THRESHOLD. */
    public const FINAL_ANSWER_CONFIDENCE_THRESHOLD = 0.50;

    /**
     * Per-result Stage 4 evidence confidence. Distinct from overallConfidence
     * and from relevance_score. Does not change the final-answer contract.
     */
    public const SEARCH_RESULT_CONFIDENCE_THRESHOLD = 0.50;

    /**
     * Authoritative final-answer eligibility: reuse synthesis overallConfidence.
     * Displayed iff performed, non-empty answer, and confidence >= 0.50.
     */
    public static function isEligibleForFinalDisplay(AnswerSynthesisExecutionReport $synthesis): bool
    {
        if (! $synthesis->performed) {
            return false;
        }

        if (trim((string) $synthesis->answer) === '') {
            return false;
        }

        return $synthesis->confidence >= self::FINAL_ANSWER_CONFIDENCE_THRESHOLD;
    }

    /**
     * @return array{
     *   primary_answer: ?string,
     *   human_status: string,
     *   user_notice_code: ?string,
     *   candidates: list<array{result_id: string, answer: string}>,
     *   sources: list<array<string, mixed>>,
     *   results: list<array<string, mixed>>,
     *   source_coverage: array<string, mixed>|null,
     *   answer_language: string,
     *   candidate_selection: array<string, mixed>
     * }
     */
    public static function fromSynthesis(
        AnswerSynthesisExecutionReport $synthesis,
        bool $sufficient,
        array $validatedEvidence = [],
        ?ScientificSearchExecutionReport $searchReport = null,
    ): array {
        $sufficient = self::isEligibleForFinalDisplay($synthesis);
        $evidenceById = [];
        foreach ($validatedEvidence as $item) {
            if ($item instanceof ScientificEvidenceItem) {
                $evidenceById[$item->evidenceId] ??= $item;
            }
        }
        $selection = ScientificAnswerCandidatePresenter::fromSynthesis($synthesis);
        $primary = $sufficient ? trim((string) $synthesis->answer) : '';
        $primary = $primary !== '' ? $primary : null;

        $candidates = [];
        foreach ($selection['answer_candidates'] as $row) {
            $text = trim((string) ($row['answer'] ?? ''));
            if ($text === '') {
                continue;
            }
            if ($primary !== null && mb_strtolower($text) === mb_strtolower($primary)) {
                continue;
            }
            $candidates[] = [
                'result_id' => (string) ($row['result_id'] ?? ('candidate-'.(string) count($candidates))),
                'answer' => $text,
            ];
        }

        $sources = [];
        foreach ($synthesis->citations as $index => $citation) {
            if (! $citation instanceof ResearchAnswerCitation) {
                continue;
            }
            $row = $citation->toArray();
            $resultId = trim((string) ($row['citation_id'] ?? ''));
            if ($resultId === '') {
                $resultId = trim((string) ($row['evidence_id'] ?? ''));
            }
            if ($resultId === '') {
                $resultId = 'src-'.$index;
            }

            $provenance = self::provenanceDisclosure($evidenceById[(string) ($row['evidence_id'] ?? '')] ?? null);
            $sourceKey = is_string($row['source_key'] ?? null) ? trim((string) $row['source_key']) : '';
            if ($sourceKey === '' && is_string($provenance['source'] ?? null)) {
                $sourceKey = trim((string) $provenance['source']);
            }
            $contributing = is_array($row['contributing_providers'] ?? null)
                ? array_values(array_filter(array_map(
                    static fn (mixed $p): string => is_string($p) ? trim($p) : '',
                    $row['contributing_providers'],
                )))
                : [];
            if ($contributing === [] && is_array($provenance['found_by_sources'] ?? null)) {
                $contributing = array_values(array_filter(array_map(
                    static fn (mixed $p): string => is_string($p) ? trim($p) : '',
                    $provenance['found_by_sources'],
                )));
            }
            if ($contributing === [] && $sourceKey !== '') {
                $contributing = [$sourceKey];
            }

            $sources[] = [
                'result_id' => $resultId,
                'title' => trim((string) ($row['title'] ?? '')) ?: 'Research source',
                'authors' => is_array($row['authors'] ?? null) ? $row['authors'] : [],
                'organization' => $row['organization'] ?? null,
                'journal' => $row['journal'] ?? null,
                'publication_year' => $row['publication_year'] ?? null,
                'provider' => $sourceKey !== '' ? $sourceKey : ($row['source_type'] ?? null),
                'source_key' => $sourceKey !== '' ? $sourceKey : null,
                'contributing_providers' => $contributing,
                'original_url' => self::originalUrl(
                    is_string($row['url'] ?? null) ? $row['url'] : null,
                    is_string($row['doi'] ?? null) ? $row['doi'] : null,
                ),
                'provenance' => $provenance,
            ];
        }

        $notice = null;
        if (! $sufficient) {
            $notice = 'insufficient_direct_evidence';
        } elseif ($synthesis->conflicts !== [] || (is_string($synthesis->uncertainty) && trim($synthesis->uncertainty) !== '')) {
            $notice = 'review_sources';
        }

        return [
            'primary_answer' => $primary,
            'human_status' => $sufficient ? self::HUMAN_ANSWERED : self::HUMAN_INSUFFICIENT,
            'user_notice_code' => $notice,
            'candidates' => $candidates,
            'sources' => $sources,
            'results' => self::researchResultsFromValidatedEvidence($validatedEvidence),
            'source_coverage' => $searchReport !== null ? self::sourceCoverage($searchReport) : null,
            'answer_language' => $synthesis->language,
            'candidate_selection' => [
                'threshold' => ScientificAnswerCandidatePresenter::PRESENTATION_THRESHOLD,
                'presented_count' => ($primary !== null ? 1 : 0) + count($candidates),
                'confidence_exposed' => false,
                'directness_unchanged' => true,
            ],
        ];
    }

    /**
     * Eligible Stage 4 scientific research results for the Results List.
     * Uses evidence-item confidence, never synthesis overallConfidence.
     * An abstract is shown only when every contributing source permits excerpt display
     * (owner decision, ADR-023 §8.38); otherwise it is withheld and the reason is given.
     *
     * @param  list<ScientificEvidenceItem|array<string, mixed>>  $validatedEvidence
     * @return list<array<string, mixed>>
     */
    public static function researchResultsFromValidatedEvidence(array $validatedEvidence): array
    {
        $rows = [];
        $seen = [];

        foreach ($validatedEvidence as $item) {
            if (! $item instanceof ScientificEvidenceItem) {
                continue;
            }
            if (! $item->isUsable()) {
                continue;
            }
            if ($item->confidence < self::SEARCH_RESULT_CONFIDENCE_THRESHOLD) {
                continue;
            }

            $resultId = self::canonicalResultId($item);
            if ($resultId === null || isset($seen[$resultId])) {
                continue;
            }
            $seen[$resultId] = true;
            $provenance = self::provenanceDisclosure($item);
            $abstract = self::abstractFromEvidence($item);
            $excerptDisplay = $provenance['usage'][ScientificSourceUsage::EXCERPT_DISPLAY->value];
            $abstractWithheld = $abstract !== null && $excerptDisplay !== ScientificSourceUsageStatus::PERMITTED->value;

            $rows[] = [
                'result_id' => $resultId,
                'title' => trim($item->publicationTitle) !== '' ? trim($item->publicationTitle) : 'Research source',
                'authors' => $item->authors,
                'organization' => $item->institution,
                'journal' => $item->journal,
                'publication_year' => $item->publicationYear,
                'doi' => $item->doi,
                'original_url' => self::originalUrl($item->url, $item->doi),
                'abstract' => $abstractWithheld ? null : $abstract,
                'abstract_withheld_reason' => $abstractWithheld ? 'excerpt_display_'.$excerptDisplay : null,
                'pdf_url' => self::pdfUrlFromEvidence($item),
                'confidence' => $item->confidence,
                'provenance' => $provenance,
            ];
        }

        return $rows;
    }

    /**
     * Where a displayed result came from and what its sources permit. Licensing is reported
     * as declared by the adapters; a missing declaration is unresolved, never permitted, and
     * the most restrictive status across contributing records is the result's status.
     *
     * @return array<string, mixed>
     */
    public static function provenanceDisclosure(?ScientificEvidenceItem $item): array
    {
        $provenance = is_array($item?->sourceAttribution['provenance'] ?? null)
            ? $item->sourceAttribution['provenance']
            : [];
        $records = [];
        $usage = [];
        foreach (ScientificSourceUsage::cases() as $case) {
            $usage[$case->value] = null;
        }
        $fullyUnresolved = false;
        $sourceRecords = is_array($provenance['records'] ?? null) ? $provenance['records'] : [];

        foreach ($sourceRecords as $record) {
            if (! is_array($record)) {
                continue;
            }
            $licensing = is_array($record['licensing'] ?? null) ? $record['licensing'] : [];
            $declared = is_array($licensing['usages'] ?? null) ? $licensing['usages'] : [];
            $recordUsage = [];
            foreach ($usage as $key => $current) {
                $status = ScientificSourceUsageStatus::tryFrom((string) ($declared[$key] ?? ''))
                    ?? ScientificSourceUsageStatus::UNRESOLVED;
                $recordUsage[$key] = $status->value;
                $usage[$key] = self::moreRestrictive($current, $status);
            }
            $licence = is_string($licensing['licence_identifier'] ?? null) ? $licensing['licence_identifier'] : null;
            if ($licence === null && array_unique(array_values($recordUsage)) === [ScientificSourceUsageStatus::UNRESOLVED->value]) {
                $fullyUnresolved = true;
            }

            $records[] = [
                'source' => $record['source_key'] ?? null,
                'original_identifier' => $record['original_identifier'] ?? null,
                'identifier_basis' => $record['identifier_basis'] ?? null,
                'record_url' => self::originalUrl(is_string($record['record_url'] ?? null) ? $record['record_url'] : null, null),
                'canonical_url_basis' => $record['canonical_url_basis'] ?? null,
                'published_at' => $record['published_at'] ?? null,
                'retrieved_at' => $record['retrieved_at'] ?? null,
                'transformations' => is_array($record['transformations'] ?? null) ? $record['transformations'] : [],
                'licence' => $licence,
                'usage' => $recordUsage,
            ];
        }

        return [
            'source' => $item?->sourceKey,
            'found_by_sources' => is_array($provenance['found_by_sources'] ?? null) ? $provenance['found_by_sources'] : [],
            'retrieved_at' => $item?->retrievedAt,
            'retrieved_at_basis' => is_string($provenance['retrieved_at_basis'] ?? null)
                ? $provenance['retrieved_at_basis']
                : 'unknown',
            'licensing_status' => $records === [] || $fullyUnresolved ? 'unresolved' : 'declared',
            'usage' => array_map(
                static fn (?ScientificSourceUsageStatus $status): string => ($status ?? ScientificSourceUsageStatus::UNRESOLVED)->value,
                $usage,
            ),
            'records' => $records,
        ];
    }

    /**
     * Which selected sources answered. A failed or unreached source makes the coverage
     * partial even when other sources produced results.
     *
     * @return array<string, mixed>
     */
    public static function sourceCoverage(ScientificSearchExecutionReport $report): array
    {
        $responded = array_values(array_unique([...$report->successfulSources, ...$report->emptySources]));
        $failed = array_values($report->failedSources);
        $unreached = array_values(array_diff($report->selectedSources, $responded, $failed));
        $failures = $report->planSummary['search_observability']['source_failures'] ?? [];

        $categories = [];
        foreach (is_array($failures) ? $failures : [] as $failure) {
            $source = is_array($failure) ? (string) ($failure['source'] ?? '') : '';
            if ($source === '' || in_array($source, $responded, true)) {
                continue;
            }
            $categories[$source][] = (string) ($failure['failure_category'] ?? '');
        }
        $categories = array_map(
            static fn (array $list): array => array_values(array_unique(array_filter($list))),
            $categories,
        );

        $complete = $report->selectedSources !== [] && $failed === [] && $unreached === [];

        return [
            'complete' => $complete,
            'search_status' => $report->status,
            'responded_sources' => $responded,
            'failed_sources' => $failed,
            'unreached_sources' => $unreached,
            'failure_categories' => $categories,
            'notice_code' => match (true) {
                $complete => null,
                $report->selectedSources === [] => 'no_sources_searched',
                default => 'partial_source_coverage',
            },
        ];
    }

    private static function moreRestrictive(
        ?ScientificSourceUsageStatus $current,
        ScientificSourceUsageStatus $candidate,
    ): ScientificSourceUsageStatus {
        $rank = static fn (ScientificSourceUsageStatus $status): int => match ($status) {
            ScientificSourceUsageStatus::PROHIBITED => 3,
            ScientificSourceUsageStatus::UNRESOLVED => 2,
            ScientificSourceUsageStatus::CONDITIONAL => 1,
            ScientificSourceUsageStatus::PERMITTED => 0,
        };

        return $current === null || $rank($candidate) > $rank($current) ? $candidate : $current;
    }

    /**
     * Session/viewer route key. Not a database identity and not a citation_id.
     * Priority: catalog source_identifier, then non-DOI/non-URL sourceId,
     * then opaque srcid-{md5(stable key)}, then evidenceId, else omit.
     * Title is never the primary identity. evidenceId is last-resort only.
     */
    public static function canonicalResultId(ScientificEvidenceItem $item): ?string
    {
        $provenance = is_array($item->sourceAttribution['provenance'] ?? null)
            ? $item->sourceAttribution['provenance']
            : [];
        $identifier = trim((string) ($provenance['source_identifier'] ?? ''));
        if ($identifier !== '' && self::isInternalIdentity($identifier)) {
            return $identifier;
        }

        $sourceId = trim($item->sourceId);
        if ($sourceId !== '' && self::isInternalIdentity($sourceId)) {
            return $sourceId;
        }

        $stable = $identifier !== '' ? $identifier : $sourceId;
        if ($stable !== '') {
            return 'srcid-'.md5($stable);
        }

        $evidenceId = trim($item->evidenceId);
        if ($evidenceId !== '') {
            return $evidenceId;
        }

        return null;
    }

    public static function isInternalIdentity(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '' || self::isDoiIdentity($trimmed)) {
            return false;
        }
        if (self::isCatalogIdentity($trimmed)) {
            return true;
        }

        return preg_match('#^https?://#i', $trimmed) !== 1;
    }

    public static function isExternalIdentity(string $value): bool
    {
        return ! self::isInternalIdentity($value);
    }

    public static function isDoiIdentity(string $value): bool
    {
        $normalized = trim((string) preg_replace('#^(?:doi:|https?://(?:dx\\.)?doi\\.org/)#i', '', trim($value)));

        return preg_match('#^10\\.\\d{4,9}/#', $normalized) === 1;
    }

    public static function isCatalogIdentity(string $value): bool
    {
        $trimmed = trim($value);
        if (preg_match('#(?:^|://)(?:[a-z0-9.-]+\\.)?openalex\\.org/#i', $trimmed) === 1) {
            return true;
        }
        if (preg_match('#^[WAIC]\\d+$#', $trimmed) === 1) {
            return true;
        }
        if (preg_match('#^(?:consensus|semantic_scholar):#', $trimmed) === 1) {
            return true;
        }

        return preg_match('#^[a-f0-9]{40}$#i', $trimmed) === 1;
    }

    /**
     * Abstract text only. Title-only evidenceText is not treated as an abstract.
     */
    public static function abstractFromEvidence(ScientificEvidenceItem $item): ?string
    {
        $text = trim((string) $item->evidenceText);
        if ($text === '') {
            return null;
        }

        if (mb_strtolower($text) === mb_strtolower(trim($item->publicationTitle))) {
            return null;
        }

        return $text;
    }

    /**
     * Provider-agnostic HTTPS PDF URL already supplied by a research adapter.
     */
    public static function pdfUrlFromEvidence(ScientificEvidenceItem $item): ?string
    {
        $provenance = is_array($item->sourceAttribution['provenance'] ?? null)
            ? $item->sourceAttribution['provenance']
            : [];
        $url = trim((string) ($provenance['open_access_pdf_url'] ?? $provenance['pdf_url'] ?? ''));

        return self::safeHttpsContentUrl($url);
    }

    public static function safeHttpsContentUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('#^https://#i', $url) !== 1) {
            return null;
        }
        if (preg_match('/[<>"\']/', $url) === 1) {
            return null;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || ! is_string($parts['host'] ?? null) || $parts['host'] === '') {
            return null;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }

    public static function originalUrl(?string $url, ?string $doi): ?string
    {
        $url = trim((string) $url);
        if ($url !== '' && preg_match('#^https?://#i', $url) === 1) {
            return self::isDisallowedExternalSearch($url) ? null : $url;
        }

        $normalized = trim((string) preg_replace('#^(?:doi:|https?://doi\.org/)#i', '', trim((string) $doi)));
        if ($normalized !== '' && preg_match('#^10\.\d{4,9}/\S+$#', $normalized) === 1) {
            return 'https://doi.org/'.$normalized;
        }

        return null;
    }

    private static function isDisallowedExternalSearch(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && preg_match('/(^|\\.)google\\.com$/i', $host) === 1;
    }
}
