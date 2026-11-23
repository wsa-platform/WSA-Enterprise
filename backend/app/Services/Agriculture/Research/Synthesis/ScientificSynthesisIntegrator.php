<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\Validation\ClaimEvidenceRelationship;
use App\Services\Agriculture\Research\Validation\EvidenceConflictAssessmentOutcome;
use App\Services\Agriculture\Research\Validation\ScientificEvidenceItem;

/**
 * Evidence-grounded synthesis packets for AnswerComposer.
 *
 * Does not invent scientific facts. Connective phrasing must stay within
 * validated claim/evidence contracts. Distinguishes genuine conflict from
 * contextual differences and preserves uncertainty / gaps.
 */
final class ScientificSynthesisIntegrator
{
    /**
     * @param  list<ResearchAnswerClaim>  $claims
     * @param  list<ScientificEvidenceItem>  $usable
     * @param  list<array<string, mixed>>  $conflicts
     * @return array{
     *     supported_findings: list<string>,
     *     partial_findings: list<string>,
     *     contextual_differences: list<array<string, mixed>>,
     *     genuine_conflicts: list<array<string, mixed>>,
     *     insufficient_claims: list<string>,
     *     evidence_gaps: list<string>,
     *     synthesis_notes: list<string>,
     *     matrix_version: string
     * }
     */
    public function integrate(
        array $claims,
        array $usable,
        array $conflicts,
        string $language = 'en',
    ): array {
        $supported = [];
        $partial = [];
        $insufficient = [];

        foreach ($claims as $claim) {
            if (! $claim instanceof ResearchAnswerClaim) {
                continue;
            }
            $text = trim($claim->claimText);
            $relationship = $claim->claimRelationship;

            if ($relationship === ClaimEvidenceRelationship::SUPPORTED && $text !== '') {
                $supported[] = $text;
            } elseif ($relationship === ClaimEvidenceRelationship::PARTIALLY_SUPPORTED && $text !== '') {
                $partial[] = $text;
            } elseif (in_array($relationship, [
                ClaimEvidenceRelationship::INSUFFICIENT_EVIDENCE,
                ClaimEvidenceRelationship::NOT_VALIDATED,
            ], true)) {
                $insufficient[] = $claim->claimId;
            } elseif ($relationship === ClaimEvidenceRelationship::CONFLICTING && $text === '') {
                $insufficient[] = $claim->claimId;
            }
        }

        $contextual = [];
        $genuine = [];
        foreach ($usable as $item) {
            $assessment = (string) ($item->qualityFactors['conflict_assessment'] ?? '');
            if ($assessment === EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT
                || $assessment === EvidenceConflictAssessmentOutcome::NOT_COMPARABLE
                || ($item->qualityFactors['contextual_difference'] ?? false) === true) {
                $contextual[] = [
                    'evidence_id' => $item->evidenceId,
                    'outcome' => $assessment !== ''
                        ? $assessment
                        : EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                    'differing_keys' => is_array($item->qualityFactors['conflict_differing_keys'] ?? null)
                        ? $item->qualityFactors['conflict_differing_keys']
                        : [],
                    'conditions' => $item->conditions,
                    'excerpt' => $this->shortExcerpt((string) ($item->evidenceText ?? '')),
                ];
            }
        }

        foreach ($conflicts as $conflict) {
            $outcome = (string) ($conflict['conflict_assessment'] ?? EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT);
            if (in_array($outcome, [
                EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                EvidenceConflictAssessmentOutcome::NOT_COMPARABLE,
            ], true)) {
                continue;
            }
            $genuine[] = $conflict;
        }

        $gaps = [];
        if ($supported === [] && $partial === [] && $genuine === []) {
            $gaps[] = 'no_directly_supported_synthesis_findings';
        }
        if ($insufficient !== []) {
            $gaps[] = 'question_claims_without_sufficient_evidence';
        }

        $notes = $this->buildNotes(
            $supported,
            $partial,
            $contextual,
            $genuine,
            $gaps,
            $language,
        );

        return [
            'supported_findings' => array_values(array_unique($supported)),
            'partial_findings' => array_values(array_unique($partial)),
            'contextual_differences' => $contextual,
            'genuine_conflicts' => $genuine,
            'insufficient_claims' => array_values(array_unique($insufficient)),
            'evidence_gaps' => array_values(array_unique($gaps)),
            'synthesis_notes' => $notes,
            'matrix_version' => 'synthesis-integrator-v1',
        ];
    }

    /**
     * User-facing synthesis appendix (not new scientific facts).
     *
     * @param  array<string, mixed>  $packet
     */
    public function formatAppendix(array $packet, string $language): string
    {
        $parts = [];
        $notes = is_array($packet['synthesis_notes'] ?? null) ? $packet['synthesis_notes'] : [];
        foreach ($notes as $note) {
            $note = trim((string) $note);
            if ($note !== '') {
                $parts[] = $note;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @param  list<string>  $supported
     * @param  list<string>  $partial
     * @param  list<array<string, mixed>>  $contextual
     * @param  list<array<string, mixed>>  $genuine
     * @param  list<string>  $gaps
     * @return list<string>
     */
    private function buildNotes(
        array $supported,
        array $partial,
        array $contextual,
        array $genuine,
        array $gaps,
        string $language,
    ): array {
        $notes = [];
        $lang = strtolower(substr($language, 0, 2));

        if ($supported !== [] && $partial !== []) {
            $notes[] = match ($lang) {
                'ar' => 'تدعم الأدلة أجزاءً من السؤال دعماً مباشراً، بينما تبقى أجزاء أخرى مدعومة جزئياً فقط.',
                'tr' => 'Kanıtlar sorunun bazı kısımlarını doğrudan, diğerlerini yalnızca kısmen destekler.',
                'fr' => 'Les preuves soutiennent directement certaines parties de la question et seulement partiellement d’autres.',
                default => 'Evidence directly supports some parts of the question; other parts are only partially supported.',
            };
        } elseif ($partial !== [] && $supported === []) {
            $notes[] = match ($lang) {
                'ar' => 'الدعم المتاح جزئي؛ لا ينبغي تعميم النتائج خارج نطاق الأدلة.',
                'tr' => 'Mevcut destek kısmiidir; bulgular kanıt kapsamı dışında genelleştirilmemelidir.',
                'fr' => 'Le soutien disponible est partiel ; les conclusions ne doivent pas être généralisées au-delà des preuves.',
                default => 'Available support is partial; findings must not be generalized beyond the evidence.',
            };
        }

        if ($contextual !== []) {
            $notes[] = match ($lang) {
                'ar' => 'تختلف بعض النتائج باختلاف الظروف التجريبية وليست بالضرورة تناقضاً علمياً مباشراً.',
                'tr' => 'Bazı bulgular deneysel koşullara göre değişir ve zorunlu olarak doğrudan bilimsel çelişki değildir.',
                'fr' => 'Certaines différences de résultats s’expliquent par des conditions expérimentales distinctes et ne constituent pas forcément une contradiction directe.',
                default => 'Some result differences are explained by distinct experimental conditions and are not necessarily direct scientific contradictions.',
            };
        }

        if ($genuine !== []) {
            $notes[] = match ($lang) {
                'ar' => 'توجد أدلة متعارضة في ظل ظروف قابلة للمقارنة؛ لم يُحسم التعارض تلقائياً.',
                'tr' => 'Karşılaştırılabilir koşullarda çelişen kanıtlar vardır; çelişki otomatik olarak çözülmedi.',
                'fr' => 'Des preuves contradictoires existent dans des conditions comparables ; le conflit n’a pas été résolu automatiquement.',
                default => 'Contradictory evidence exists under comparable conditions; the conflict was not automatically resolved.',
            };
        }

        if (in_array('question_claims_without_sufficient_evidence', $gaps, true)) {
            $notes[] = match ($lang) {
                'ar' => 'بعض عناصر السؤال تفتقر إلى أدلة كافية للتحقق.',
                'tr' => 'Sorunun bazı unsurları doğrulama için yeterli kanıttan yoksundur.',
                'fr' => 'Certaines parties de la question manquent de preuves suffisantes pour validation.',
                default => 'Some parts of the question lack sufficient evidence for validation.',
            };
        }

        return $notes;
    }

    private function shortExcerpt(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= 180) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, 177)).'...';
    }
}
