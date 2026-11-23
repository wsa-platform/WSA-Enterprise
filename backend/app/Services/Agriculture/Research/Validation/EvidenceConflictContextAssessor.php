<?php

namespace App\Services\Agriculture\Research\Validation;

/**
 * Classifies opposing evidence findings using experimental context.
 *
 * Does not invent metadata. Missing conditions → INSUFFICIENT_INFORMATION.
 * Differing conditions → RESOLVED_BY_CONTEXT (not a genuine contradiction).
 * Comparable conditions with opposing polarity → GENUINE_CONFLICT.
 */
final class EvidenceConflictContextAssessor
{
    /** @var list<string> */
    private const CONDITION_KEYS = [
        'irrigation_method',
        'water_quantity',
        'location',
        'climate',
        'variety',
        'genotype',
        'soil',
        'dosage',
        'application_rate',
        'treatment',
        'control',
        'duration',
        'growth_stage',
        'experimental_design',
        'measurement_method',
        'sample_size',
    ];

    /**
     * @return array{
     *     outcome: string,
     *     differing_keys: list<string>,
     *     comparable: bool,
     *     reason: string
     * }
     */
    public function assessPair(ScientificEvidenceItem $a, ScientificEvidenceItem $b): array
    {
        if ($this->entitiesIncompatible($a, $b)) {
            return [
                'outcome' => EvidenceConflictAssessmentOutcome::NOT_COMPARABLE,
                'differing_keys' => ['crop_or_entity'],
                'comparable' => false,
                'reason' => 'different_scientific_entities',
            ];
        }

        if ($this->measurementMethodsIncompatible($a, $b)) {
            return [
                'outcome' => EvidenceConflictAssessmentOutcome::NOT_COMPARABLE,
                'differing_keys' => ['measurement_method'],
                'comparable' => false,
                'reason' => 'incompatible_measurement_methods',
            ];
        }

        $ca = $this->conditionMap($a);
        $cb = $this->conditionMap($b);

        if ($ca === [] && $cb === []) {
            return [
                'outcome' => EvidenceConflictAssessmentOutcome::INSUFFICIENT_INFORMATION,
                'differing_keys' => [],
                'comparable' => false,
                'reason' => 'missing_experimental_context',
            ];
        }

        $differing = [];
        $keys = array_values(array_unique(array_merge(array_keys($ca), array_keys($cb))));
        foreach ($keys as $key) {
            $va = $ca[$key] ?? null;
            $vb = $cb[$key] ?? null;
            if ($va === null || $vb === null) {
                // One side lacks a key the other has → incomplete comparability.
                if ($va !== null || $vb !== null) {
                    $differing[] = $key;
                }

                continue;
            }
            if ($va !== $vb) {
                $differing[] = $key;
            }
        }

        if ($differing !== []) {
            return [
                'outcome' => EvidenceConflictAssessmentOutcome::RESOLVED_BY_CONTEXT,
                'differing_keys' => array_values(array_unique($differing)),
                'comparable' => false,
                'reason' => 'experimental_conditions_differ',
            ];
        }

        // Shared comparable context present and aligned → opposing polarity is genuine.
        return [
            'outcome' => EvidenceConflictAssessmentOutcome::GENUINE_CONFLICT,
            'differing_keys' => [],
            'comparable' => true,
            'reason' => 'opposing_findings_under_comparable_conditions',
        ];
    }

    private function entitiesIncompatible(ScientificEvidenceItem $a, ScientificEvidenceItem $b): bool
    {
        $ea = mb_strtolower(trim((string) ($a->cropOrEntity ?? '')));
        $eb = mb_strtolower(trim((string) ($b->cropOrEntity ?? '')));
        if ($ea === '' || $eb === '') {
            return false;
        }

        return $ea !== $eb
            && ! str_contains($ea, $eb)
            && ! str_contains($eb, $ea);
    }

    private function measurementMethodsIncompatible(ScientificEvidenceItem $a, ScientificEvidenceItem $b): bool
    {
        $ma = $this->conditionMap($a)['measurement_method'] ?? null;
        $mb = $this->conditionMap($b)['measurement_method'] ?? null;
        if ($ma === null || $mb === null) {
            return false;
        }

        return $ma !== $mb;
    }

    /**
     * @return array<string, string>
     */
    private function conditionMap(ScientificEvidenceItem $item): array
    {
        $map = [];
        $conditions = is_array($item->conditions) ? $item->conditions : [];
        foreach (self::CONDITION_KEYS as $key) {
            if (! array_key_exists($key, $conditions)) {
                continue;
            }
            $value = $this->normalizeScalar($conditions[$key]);
            if ($value !== null) {
                $map[$key] = $value;
            }
        }

        // Common aliases from upstream metadata.
        foreach ([
            'irrigation' => 'irrigation_method',
            'irrigation_system' => 'irrigation_method',
            'method' => 'irrigation_method',
            'region' => 'location',
            'site' => 'location',
            'cultivar' => 'variety',
            'dose' => 'dosage',
            'rate' => 'application_rate',
        ] as $alias => $canonical) {
            if (isset($map[$canonical]) || ! array_key_exists($alias, $conditions)) {
                continue;
            }
            $value = $this->normalizeScalar($conditions[$alias]);
            if ($value !== null) {
                $map[$canonical] = $value;
            }
        }

        if ($map === []) {
            $map = $this->inferHintsFromText((string) ($item->evidenceText ?? ''), (string) ($item->publicationTitle ?? ''));
        }

        return $map;
    }

    /**
     * Conservative text hints only — never invents numeric experimental values.
     *
     * @return array<string, string>
     */
    private function inferHintsFromText(string $evidenceText, string $title): array
    {
        $hay = mb_strtolower(trim($evidenceText.' '.$title));
        if ($hay === '') {
            return [];
        }

        $map = [];
        if (preg_match('/\b(?:drip|drip irrigation|تنقيط)\b/u', $hay) === 1) {
            $map['irrigation_method'] = 'drip';
        } elseif (preg_match('/\b(?:sprinkler|رش|رشاش)\b/u', $hay) === 1) {
            $map['irrigation_method'] = 'sprinkler';
        } elseif (preg_match('/\b(?:flood|furrow|غمر|خطوط)\b/u', $hay) === 1) {
            $map['irrigation_method'] = 'surface';
        } elseif (preg_match('/\b(?:deficit irrigation|الري الناقص|ري ناقص)\b/u', $hay) === 1) {
            $map['irrigation_method'] = 'deficit';
        }

        if (preg_match('/\b(?:arid|desert|جاف|صحراو|dryland)\b/u', $hay) === 1) {
            $map['climate'] = 'arid';
        } elseif (preg_match('/\b(?:humid|رطب)\b/u', $hay) === 1) {
            $map['climate'] = 'humid';
        }

        if (preg_match('/\b(?:greenhouse|صوبة|بيت بلاستيك)\b/u', $hay) === 1) {
            $map['experimental_design'] = 'greenhouse';
        } elseif (preg_match('/\b(?:field trial|حقل|field experiment)\b/u', $hay) === 1) {
            $map['experimental_design'] = 'field';
        }

        return $map;
    }

    private function normalizeScalar(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (! is_string($value)) {
            return null;
        }
        $trimmed = mb_strtolower(trim($value));

        return $trimmed !== '' ? $trimmed : null;
    }
}
