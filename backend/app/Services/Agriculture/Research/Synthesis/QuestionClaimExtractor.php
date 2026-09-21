<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Phase-5 Unit A — extract QuestionClaims from the plan/question surface only.
 *
 * Does not read evidence. Does not call Catalog. Does not redesign QUS/KQP.
 */
final class QuestionClaimExtractor
{
    /**
     * @return list<QuestionClaim>
     */
    public function extract(KnowledgeQueryPlan $plan): array
    {
        $query = $plan->normalizedQuery;
        $questionText = trim($query->originalQuestion !== '' ? $query->originalQuestion : $query->normalizedQuestion);
        $questionLanguage = $this->normalizeLanguage($query->language);
        $answerLanguage = $this->resolveAnswerLanguage($plan);
        $entity = $this->resolveEntity($plan);
        $location = $query->location !== null && trim($query->location) !== '' ? trim($query->location) : null;
        $time = $this->resolveTime($plan);
        $intent = trim($plan->researchIntent) !== '' ? trim($plan->researchIntent) : 'scientific_explanation';
        $requested = $this->distinctRequestedInformation($plan);
        $limitations = [];

        if ($this->isComparisonWithoutSecondEntity($plan)) {
            $limitations[] = 'comparison_decomposition_unsupported';
        }

        if ($requested === []) {
            return [
                new QuestionClaim(
                    claimId: 'qc-1',
                    questionText: $questionText,
                    claimText: $questionText,
                    claimIntent: $intent,
                    entity: $entity,
                    property: $this->primaryProperty($plan),
                    location: $location,
                    time: $time,
                    answerLanguage: $answerLanguage,
                    questionLanguage: $questionLanguage,
                    limitations: $limitations,
                    scope: $this->scope($plan, null),
                ),
            ];
        }

        if (count($requested) === 1) {
            $property = $requested[0];

            return [
                new QuestionClaim(
                    claimId: 'qc-1',
                    questionText: $questionText,
                    claimText: $this->claimTextForProperty($questionText, $property),
                    claimIntent: $intent,
                    entity: $entity,
                    property: $property,
                    location: $location,
                    time: $time,
                    answerLanguage: $answerLanguage,
                    questionLanguage: $questionLanguage,
                    limitations: $limitations,
                    scope: $this->scope($plan, $property),
                ),
            ];
        }

        $claims = [];
        foreach (array_values($requested) as $index => $property) {
            $claims[] = new QuestionClaim(
                claimId: 'qc-'.($index + 1),
                questionText: $questionText,
                claimText: $this->claimTextForProperty($questionText, $property),
                claimIntent: $intent,
                entity: $entity,
                property: $property,
                location: $location,
                time: $time,
                answerLanguage: $answerLanguage,
                questionLanguage: $questionLanguage,
                limitations: $limitations,
                scope: $this->scope($plan, $property),
            );
        }

        return $claims;
    }

    private function resolveAnswerLanguage(KnowledgeQueryPlan $plan): string
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        $answerLanguage = $this->normalizeLanguage((string) ($constraints['answer_language'] ?? ''));
        if ($answerLanguage !== '') {
            return $answerLanguage;
        }

        // Prefer question language over platform/UI locale (R2). Never use app()->getLocale() here.
        return $this->normalizeLanguage($plan->normalizedQuery->language) ?: 'en';
    }

    private function normalizeLanguage(string $language): string
    {
        $normalized = strtolower(substr(trim($language), 0, 2));

        return in_array($normalized, ['en', 'ar', 'tr', 'fr'], true) ? $normalized : '';
    }

    private function resolveEntity(KnowledgeQueryPlan $plan): ?string
    {
        $query = $plan->normalizedQuery;
        foreach ([
            is_string($query->cropId) ? $query->cropId : null,
            is_string($query->crop) ? $query->crop : null,
            is_array($plan->subjectEntity) ? (string) ($plan->subjectEntity['value'] ?? '') : null,
            is_array($query->subject) ? (string) ($query->subject['value'] ?? '') : null,
        ] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveTime(KnowledgeQueryPlan $plan): ?string
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        foreach (['year', 'year_code', 'fao_year', 'time'] as $key) {
            $value = trim((string) ($constraints[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function primaryProperty(KnowledgeQueryPlan $plan): ?string
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        foreach (['requested_property', 'requested_property_key', 'required_evidence_type'] as $key) {
            $value = trim((string) ($constraints[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $topic = trim($plan->normalizedQuery->topic);

        return $topic !== '' ? $topic : null;
    }

    /**
     * @return list<string>
     */
    private function distinctRequestedInformation(KnowledgeQueryPlan $plan): array
    {
        $items = [];
        foreach ($plan->requestedInformation as $item) {
            $normalized = strtolower(trim((string) $item));
            if ($normalized === '' || isset($items[$normalized])) {
                continue;
            }
            $items[$normalized] = trim((string) $item);
        }

        return array_values($items);
    }

    private function claimTextForProperty(string $questionText, string $property): string
    {
        $property = trim($property);
        if ($property === '') {
            return $questionText;
        }

        return trim($questionText.' ['.$property.']');
    }

    private function isComparisonWithoutSecondEntity(KnowledgeQueryPlan $plan): bool
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];
        $questionType = strtolower(trim((string) ($constraints['question_type'] ?? '')));
        $intent = strtolower(trim($plan->researchIntent));
        $isComparison = $questionType === 'comparison' || $intent === 'comparison';
        if (! $isComparison) {
            return false;
        }

        $second = trim((string) (
            $constraints['comparison_entity']
            ?? $constraints['entity_b']
            ?? $plan->contextInput['comparison_entity']
            ?? $plan->contextInput['entity_b']
            ?? ''
        ));

        return $second === '';
    }

    /**
     * @return array<string, mixed>
     */
    private function scope(KnowledgeQueryPlan $plan, ?string $property): array
    {
        $constraints = is_array($plan->normalizedQuery->constraints) ? $plan->normalizedQuery->constraints : [];

        return [
            'research_intent' => $plan->researchIntent,
            'topic' => $plan->normalizedQuery->topic,
            'question_type' => $constraints['question_type'] ?? null,
            'required_evidence_type' => $constraints['required_evidence_type'] ?? null,
            'property' => $property,
            'context_input_keys' => array_keys($plan->contextInput),
            'home_or_crop' => trim((string) ($plan->contextInput['selected_crop_id'] ?? '')) !== ''
                ? 'crop'
                : 'home',
        ];
    }
}
