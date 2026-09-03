<?php

namespace App\Services\Agriculture\Diagnosis\KnowledgeBase;

use App\Services\Agriculture\Diagnosis\DiagnosisConfidenceBand;
use App\Services\Agriculture\Diagnosis\DiagnosisEvidence;
use App\Services\Agriculture\Diagnosis\VisionObservation;

/**
 * Deterministic diagnosis knowledge matcher (no Research Agent, no network).
 */
class DiagnosisKnowledgeMatcher
{
    public function __construct(
        private readonly InMemoryDiagnosisKnowledgeStore $store,
    ) {}

    /**
     * @return list<DiagnosisKnowledgeMatchResult>
     */
    public function match(DiagnosisKnowledgeQuery $query): array
    {
        $records = $query->verifiedOnly
            ? $this->store->allVerified()
            : $this->store->all(includeRaw: true);

        $haystack = $this->buildHaystack($query);
        $results = [];

        foreach ($records as $record) {
            $match = $this->scoreRecord($record, $query, $haystack);
            if ($match !== null) {
                $results[] = $match;
            }
        }

        usort(
            $results,
            static fn (DiagnosisKnowledgeMatchResult $a, DiagnosisKnowledgeMatchResult $b): int => $b->matchScore <=> $a->matchScore
        );

        return $results;
    }

    /**
     * @param  array{text: string, observation_ids: list<string>, parts: list<string>}  $haystack
     */
    private function scoreRecord(
        DiagnosisKnowledgeRecord $record,
        DiagnosisKnowledgeQuery $query,
        array $haystack,
    ): ?DiagnosisKnowledgeMatchResult {
        $reasons = [];
        $score = 0.0;
        $matchedObservationIds = [];
        $supporting = [];
        $contradicting = [];

        // 1) Exact scientific-name match
        if ($query->scientificName !== null && $record->scientificName !== null) {
            if ($this->normalize($query->scientificName) === $this->normalize($record->scientificName)) {
                $score += 0.35;
                $reasons[] = 'exact_scientific_name';
            }
        }

        // 2) Common-name match
        $commonPool = array_merge([$record->commonName], $record->commonNames);
        if ($query->commonName !== null) {
            foreach ($commonPool as $name) {
                if ($this->normalize($query->commonName) === $this->normalize($name)
                    || str_contains($this->normalize($query->commonName), $this->normalize($name))
                    || str_contains($this->normalize($name), $this->normalize($query->commonName))) {
                    $score += 0.12;
                    $reasons[] = 'common_name';
                    break;
                }
            }
        }

        // 3) Alias match
        $aliasPool = array_merge($record->aliases, $commonPool);
        foreach (array_merge($query->aliases, $query->commonName !== null ? [$query->commonName] : []) as $alias) {
            foreach ($aliasPool as $known) {
                if ($this->normalize($alias) === $this->normalize($known)) {
                    $score += 0.12;
                    $reasons[] = 'alias';
                    break 2;
                }
            }
        }

        // Arabic / bilingual common-name tokens in aliases
        foreach ($aliasPool as $known) {
            if ($this->containsArabic($known) && str_contains($haystack['text'], $this->normalize($known))) {
                $score += 0.08;
                $reasons[] = 'arabic_or_localized_alias';
                break;
            }
        }

        // 4) Crop match
        $cropKey = $query->cropKey ?? $query->context?->cropType;
        $plantName = $query->context?->plantName;
        if ($record->cropKeys !== []) {
            $cropMatched = false;
            foreach ($record->cropKeys as $crop) {
                $nCrop = $this->normalize($crop);
                if ($cropKey !== null && (str_contains($this->normalize($cropKey), $nCrop) || str_contains($nCrop, $this->normalize($cropKey)))) {
                    $cropMatched = true;
                }
                if ($plantName !== null && (str_contains($this->normalize($plantName), $nCrop) || str_contains($nCrop, $this->normalize($plantName)))) {
                    $cropMatched = true;
                }
            }
            if ($cropMatched) {
                $score += 0.1;
                $reasons[] = 'crop';
            } elseif ($cropKey !== null || $plantName !== null) {
                // Soft penalty when crop context conflicts with crop-scoped records.
                $score -= 0.05;
                $reasons[] = 'crop_mismatch_soft';
            }
        }

        // 5) Symptom match
        $symptomHits = 0;
        foreach ($record->symptoms as $symptom) {
            if (str_contains($haystack['text'], $this->normalize($symptom))) {
                $symptomHits++;
            }
        }
        if ($symptomHits > 0) {
            $score += min(0.2, 0.07 * $symptomHits);
            $reasons[] = 'symptom';
            $supporting[] = 'Matched '.$symptomHits.' symptom descriptor(s).';
        }

        // 6) Plant-part match
        if ($query->plantPart !== null) {
            foreach ($record->plantParts as $part) {
                if ($this->normalize($query->plantPart) === $this->normalize($part)
                    || str_contains($haystack['text'], $this->normalize($part))) {
                    $score += 0.08;
                    $reasons[] = 'plant_part';
                    break;
                }
            }
        } else {
            foreach ($record->plantParts as $part) {
                if (str_contains($haystack['text'], $this->normalize($part))) {
                    $score += 0.05;
                    $reasons[] = 'plant_part';
                    break;
                }
            }
        }

        // 7) Causal category / targeted filters
        if ($query->causalClass !== null && $this->normalize($query->causalClass) === $this->normalize($record->causalClass)) {
            $score += 0.1;
            $reasons[] = 'causal_category';
        }
        if ($query->category !== null && $this->normalize($query->category) === $this->normalize($record->category)) {
            $score += 0.08;
            $reasons[] = 'category';
        }
        foreach ([
            'disease' => $query->disease,
            'pathogen' => $query->pathogen,
            'pest' => $query->pest,
            'nutrient' => $query->nutrient,
            'abiotic_stress' => $query->abioticStress,
        ] as $label => $value) {
            if ($value === null) {
                continue;
            }
            $n = $this->normalize($value);
            $pool = strtolower(implode(' ', [
                $record->id,
                $record->commonName,
                (string) $record->scientificName,
                $record->category,
                $record->causalClass,
                (string) $record->pathogenType,
                implode(' ', $record->aliases),
                implode(' ', $record->symptoms),
            ]));
            if (str_contains($pool, $n)) {
                $score += 0.14;
                $reasons[] = $label;
            }
        }

        // 8) Multi-observation / pattern match + negative evidence
        $patternHits = 0;
        foreach ($record->observationPatterns as $pattern) {
            $localHit = false;
            foreach ($pattern->observationTypes as $type) {
                foreach ($query->observations as $observation) {
                    if ($this->normalize($observation->type) === $this->normalize($type)
                        || str_contains($this->normalize($observation->type), $this->normalize($type))) {
                        $localHit = true;
                        $matchedObservationIds[] = $observation->id;
                    }
                }
            }
            foreach ($pattern->keywords as $keyword) {
                $nKeyword = $this->normalize($keyword);
                if (str_contains($haystack['text'], $nKeyword)) {
                    $localHit = true;
                    foreach ($query->observations as $observation) {
                        $obsText = $this->normalize(
                            $observation->type.' '.$observation->description.' '.implode(' ', $observation->supportingCues)
                        );
                        if (str_contains($obsText, $nKeyword)) {
                            $matchedObservationIds[] = $observation->id;
                        }
                    }
                }
            }
            foreach ($pattern->negativeKeywords as $neg) {
                if (str_contains($haystack['text'], $this->normalize($neg))) {
                    $score -= 0.12;
                    $contradicting[] = 'Negative cue present: '.$neg;
                    $reasons[] = 'negative_evidence';
                }
            }
            if ($localHit) {
                $patternHits++;
            }
        }

        if ($patternHits > 0) {
            $score += min(0.28, 0.1 * $patternHits);
            $reasons[] = $patternHits > 1 ? 'multiple_observation' : 'observation';
            $supporting[] = 'Matched '.$patternHits.' observation pattern(s).';
        }

        $matchedObservationIds = array_values(array_unique($matchedObservationIds));
        if (count($matchedObservationIds) > 1 && ! in_array('multiple_observation', $reasons, true)) {
            $reasons[] = 'multiple_observation';
            $score = min(1.0, $score + 0.04);
        }

        foreach ($record->supportingEvidenceNotes as $note) {
            $supporting[] = $note;
        }
        foreach ($record->contradictingEvidenceNotes as $note) {
            $contradicting[] = $note;
        }

        // Explicit differential contradicting relations boost caution
        foreach ($record->differentials as $diff) {
            if ($diff->relation === DiagnosisDifferentialEntry::RELATION_CONTRADICTING) {
                $contradicting[] = $diff->commonName.($diff->notes ? ': '.$diff->notes : '');
            }
        }

        if ($matchedObservationIds === [] && $query->observations !== []) {
            $matchedObservationIds = array_map(
                static fn (VisionObservation $o): string => $o->id,
                $query->observations,
            );
        }

        // Require some positive signal
        $positiveReasons = array_values(array_filter(
            $reasons,
            static fn (string $r): bool => ! in_array($r, ['crop_mismatch_soft', 'negative_evidence'], true),
        ));

        if ($positiveReasons === [] || $score < 0.12) {
            return null;
        }

        $score = DiagnosisConfidenceBand::clampImageAloneScore(max(0.0, $score));
        if ($score >= 1.0) {
            $score = DiagnosisConfidenceBand::MAX_IMAGE_ALONE_SCORE;
        }

        $insufficient = $score < 0.30 || ($patternHits === 0 && $symptomHits === 0 && ! in_array('exact_scientific_name', $reasons, true));
        $confidence = DiagnosisKnowledgeConfidenceBand::fromScore($score);

        $safety = match (true) {
            $insufficient => DiagnosisKnowledgeSafetyStatus::INSUFFICIENT_EVIDENCE,
            $contradicting !== [] && $score < 0.65 => DiagnosisKnowledgeSafetyStatus::HUMAN_REVIEW_REQUIRED,
            $confidence === DiagnosisKnowledgeConfidenceBand::VERY_LOW => DiagnosisKnowledgeSafetyStatus::INSUFFICIENT_EVIDENCE,
            $confidence === DiagnosisKnowledgeConfidenceBand::LOW || $contradicting !== [] => DiagnosisKnowledgeSafetyStatus::CAUTION,
            $confidence === DiagnosisKnowledgeConfidenceBand::HIGH && $contradicting === [] => DiagnosisKnowledgeSafetyStatus::SAFE,
            default => DiagnosisKnowledgeSafetyStatus::CAUTION,
        };

        if (count($record->differentials) >= 3 && $score < 0.7) {
            $safety = DiagnosisKnowledgeSafetyStatus::HUMAN_REVIEW_REQUIRED;
        }

        $evidence = [
            new DiagnosisEvidence(
                kind: 'knowledge_base_match',
                summary: 'Matched diagnosis knowledge record '.$record->id.' via: '.implode(', ', array_unique($reasons)),
                observationIds: $matchedObservationIds,
                sourceLabel: $record->sources[0]->label ?? 'diagnosis_knowledge_base',
                fromKnowledgeSupport: true,
            ),
        ];

        if ($contradicting !== []) {
            $evidence[] = new DiagnosisEvidence(
                kind: 'contradicting_evidence',
                summary: 'Contradicting signals preserved: '.implode('; ', array_slice($contradicting, 0, 3)),
                observationIds: $matchedObservationIds,
                sourceLabel: 'diagnosis_knowledge_base',
                fromKnowledgeSupport: true,
            );
        }

        return new DiagnosisKnowledgeMatchResult(
            record: $record,
            matchScore: $score,
            confidenceBand: $confidence,
            safetyStatus: $safety,
            matchedObservationIds: $matchedObservationIds,
            supportingEvidence: array_values(array_unique($supporting)),
            contradictingEvidence: array_values(array_unique($contradicting)),
            evidence: $evidence,
            matchReasons: array_values(array_unique($reasons)),
            insufficientEvidence: $insufficient,
        );
    }

    /**
     * @return array{text: string, observation_ids: list<string>, parts: list<string>}
     */
    private function buildHaystack(DiagnosisKnowledgeQuery $query): array
    {
        $parts = [];
        $ids = [];

        foreach ($query->observations as $observation) {
            $ids[] = $observation->id;
            $parts[] = $observation->type;
            $parts[] = $observation->description;
            $parts[] = (string) $observation->locationOnPlant;
            $parts[] = (string) $observation->severityHint;
            foreach ($observation->supportingCues as $cue) {
                $parts[] = $cue;
            }
        }

        foreach ($query->symptoms as $symptom) {
            $parts[] = $symptom;
        }

        if ($query->context !== null) {
            $parts[] = (string) $query->context->plantName;
            $parts[] = (string) $query->context->cropType;
            $parts[] = (string) $query->context->notes;
            foreach ($query->context->symptomsDescribed as $symptom) {
                $parts[] = $symptom;
            }
        }

        foreach ([$query->cropKey, $query->commonName, $query->plantPart, $query->disease, $query->pathogen, $query->pest, $query->nutrient, $query->abioticStress] as $extra) {
            if ($extra !== null) {
                $parts[] = $extra;
            }
        }

        foreach ($query->aliases as $alias) {
            $parts[] = $alias;
        }

        return [
            'text' => $this->normalize(implode(' ', $parts)),
            'observation_ids' => $ids,
            'parts' => $parts,
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    private function containsArabic(string $value): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $value);
    }
}
