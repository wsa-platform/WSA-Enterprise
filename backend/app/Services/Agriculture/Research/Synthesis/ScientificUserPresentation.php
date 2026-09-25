<?php

namespace App\Services\Agriculture\Research\Synthesis;

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
     *   answer_language: string,
     *   candidate_selection: array<string, mixed>
     * }
     */
    public static function fromSynthesis(AnswerSynthesisExecutionReport $synthesis, bool $sufficient): array
    {
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
            'answer_language' => $synthesis->language,
            'candidate_selection' => [
                'threshold' => ScientificAnswerCandidatePresenter::PRESENTATION_THRESHOLD,
                'presented_count' => ($primary !== null ? 1 : 0) + count($candidates),
                'confidence_exposed' => false,
                'directness_unchanged' => true,
            ],
        ];
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
