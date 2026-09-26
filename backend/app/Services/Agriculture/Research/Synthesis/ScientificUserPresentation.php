<?php

namespace App\Services\Agriculture\Research\Synthesis;

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
     *   answer_language: string,
     *   candidate_selection: array<string, mixed>
     * }
     */
    public static function fromSynthesis(
        AnswerSynthesisExecutionReport $synthesis,
        bool $sufficient,
        array $validatedEvidence = [],
    ): array {
        $sufficient = self::isEligibleForFinalDisplay($synthesis);
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

            $sources[] = [
                'result_id' => $resultId,
                'title' => trim((string) ($row['title'] ?? '')) ?: 'Research source',
                'authors' => is_array($row['authors'] ?? null) ? $row['authors'] : [],
                'organization' => $row['organization'] ?? null,
                'journal' => $row['journal'] ?? null,
                'publication_year' => $row['publication_year'] ?? null,
                'original_url' => self::originalUrl(
                    is_string($row['url'] ?? null) ? $row['url'] : null,
                    is_string($row['doi'] ?? null) ? $row['doi'] : null,
                ),
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

            $rows[] = [
                'result_id' => $resultId,
                'title' => trim($item->publicationTitle) !== '' ? trim($item->publicationTitle) : 'Research source',
                'authors' => $item->authors,
                'organization' => $item->institution,
                'journal' => $item->journal,
                'publication_year' => $item->publicationYear,
                'doi' => $item->doi,
                'original_url' => self::originalUrl($item->url, $item->doi),
                'abstract' => self::abstractFromEvidence($item),
                'pdf_url' => self::pdfUrlFromEvidence($item),
                'confidence' => $item->confidence,
            ];
        }

        return $rows;
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
