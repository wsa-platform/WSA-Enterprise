<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use App\Services\Agriculture\Research\KnowledgeQueryPlan;

/**
 * Deterministic, side-effect-free scientific query compilation.
 *
 * CSQ is semantic authority. Profile decides representation only.
 * No HTTP, LLM, DB, adapter execution, evidence, answers, ranking, or confidence.
 */
final class ScientificQueryCompiler
{
    /**
     * Whether the profile can represent this CSQ without inventing missing meaning.
     */
    public function canRepresent(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): bool {
        if (! $profile->active) {
            return false;
        }

        if ($profile->modality === ScientificSourceQueryModality::LITERATURE) {
            return $this->hasAnySupportedLexicalToken($question, $profile);
        }

        if ($profile->modality === ScientificSourceQueryModality::SCIENTIFIC_DATA) {
            // Structured sources need at least one resolved identity or constraint field
            // that the profile supports — never invent codes here.
            return $this->hasAnySupportedStructuredProjection($question, $profile);
        }

        if ($profile->modality === ScientificSourceQueryModality::INSTITUTIONAL) {
            return $this->hasAnySupportedLexicalToken($question, $profile);
        }

        return false;
    }

    public function compile(
        CanonicalScientificQuestion $question,
        KnowledgeQueryPlan $plan,
        ScientificSourceProfile $profile,
    ): ScientificQueryBundle {
        // Plan is accepted for future plan-aware projections; compiler never executes adapters.
        unset($plan);

        $identity = $this->projectScientificIdentity($question);

        if (! $this->canRepresent($question, $profile)) {
            return new ScientificQueryBundle(
                sourceKey: $profile->sourceKey,
                sourceQuery: '',
                variants: [],
                structuredFilters: [],
                scientificIdentity: $identity,
                representable: false,
                skipReason: $profile->active ? 'unsupported_fields' : 'inactive_profile',
            );
        }

        $filters = $this->projectStructuredFilters($question, $profile);
        $sourceQuery = $this->assembleSourceQuery($question, $profile);

        return new ScientificQueryBundle(
            sourceKey: $profile->sourceKey,
            sourceQuery: $sourceQuery,
            variants: $sourceQuery !== '' ? [$sourceQuery] : [],
            structuredFilters: $filters,
            scientificIdentity: $identity,
            representable: true,
            skipReason: null,
        );
    }

    /**
     * Question-level identity projection for traceability (not answer_id / R8.11).
     *
     * @return array<string, mixed>
     */
    public function projectScientificIdentity(CanonicalScientificQuestion $question): array
    {
        return [
            'entity' => $this->roleSnapshot(
                surface: $question->entity->surface,
                normalized: $question->entity->normalized,
                resolution: $question->entity->resolution,
                canonicalId: $question->entity->canonicalId,
                canonicalNamespace: $question->entity->canonicalNamespace,
            ),
            'target' => [
                'surface' => $this->nullableTrim($question->target->surface),
                'normalized' => $this->nullableTrim($question->target->normalizedKey),
                'kind' => $question->target->kind,
                'resolution' => $question->target->resolution,
                'canonical_id' => $this->nullableTrim($question->target->canonicalId),
                'canonical_namespace' => $this->nullableTrim($question->target->canonicalNamespace),
            ],
            'process' => $this->roleSnapshot(
                surface: $question->process->surface,
                normalized: $question->process->normalized,
                resolution: $question->process->resolution,
                canonicalId: $question->process->canonicalId,
                canonicalNamespace: $question->process->canonicalNamespace,
            ),
            'property' => [
                'key' => $this->nullableTrim($question->property->key),
                'surface' => $this->nullableTrim($question->property->surface),
                'of_role' => $this->nullableTrim($question->property->ofRole),
                'unit' => $this->nullableTrim($question->property->unit),
                'resolution' => $question->property->resolution,
            ],
            'relation' => [
                'type' => $question->relation->type,
                'from' => $this->nullableTrim($question->relation->from),
                'to' => $this->nullableTrim($question->relation->to),
                'state' => $question->relation->state,
            ],
            'context' => [
                'scientific_sense' => $this->nullableTrim($question->context->scientificSense),
                'question_type' => $this->nullableTrim($question->context->questionType),
            ],
            'time' => [
                'year' => $question->time->year,
                'period' => $this->nullableTrim($question->time->period),
                'start_year' => $question->time->startYear,
                'end_year' => $question->time->endYear,
            ],
            'geography' => [
                'label' => $this->nullableTrim($question->geography->label),
                'country' => $this->nullableTrim($question->geography->country),
                'region' => $this->nullableTrim($question->geography->region),
                'canonical_id' => $this->nullableTrim($question->geography->canonicalId),
                'canonical_namespace' => $this->nullableTrim($question->geography->canonicalNamespace),
            ],
            // Crop binding is UI/context — never promoted to entity identity.
            'crop_binding_present' => $this->nullableTrim($question->cropBinding->cropId) !== null
                || $this->nullableTrim($question->cropBinding->cropLabel) !== null,
        ];
    }

    private function hasAnySupportedLexicalToken(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): bool {
        return $this->collectLexicalTokens($question, $profile) !== [];
    }

    private function hasAnySupportedStructuredProjection(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): bool {
        return $this->projectStructuredFilters($question, $profile) !== []
            || $this->collectLexicalTokens($question, $profile) !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function projectStructuredFilters(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): array {
        $filters = [];

        if ($profile->supportsField('entity')) {
            $entity = $this->entityLexical($question);
            if ($entity !== null) {
                $filters['entity'] = $entity;
            }
            if ($question->entity->resolution === CanonicalScientificQuestion::RESOLUTION_RESOLVED
                && $this->nullableTrim($question->entity->canonicalId) !== null) {
                $filters['entity_canonical_id'] = $question->entity->canonicalId;
                $filters['entity_canonical_namespace'] = $question->entity->canonicalNamespace;
            }
        }

        if ($profile->supportsField('property')) {
            $property = $this->nullableTrim($question->property->key)
                ?? $this->nullableTrim($question->property->surface);
            if ($property !== null) {
                $filters['property'] = $property;
            }
        }

        if ($profile->supportsField('geography')) {
            $geo = $this->nullableTrim($question->geography->country)
                ?? $this->nullableTrim($question->geography->label)
                ?? $this->nullableTrim($question->geography->region);
            if ($geo !== null) {
                $filters['geography'] = $geo;
            }
            if ($this->nullableTrim($question->geography->canonicalId) !== null) {
                $filters['geography_canonical_id'] = $question->geography->canonicalId;
            }
        }

        if ($profile->supportsField('time') && $question->time->year !== null) {
            $filters['year'] = $question->time->year;
        }

        return $filters;
    }

    private function assembleSourceQuery(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): string {
        if ($profile->queryStyle === 'structured_filters') {
            // Structured sources prefer filters; lexical string is a bounded diagnostic fallback.
            $parts = [];
            foreach (['entity', 'property', 'geography', 'year'] as $key) {
                $filters = $this->projectStructuredFilters($question, $profile);
                if (isset($filters[$key]) && is_scalar($filters[$key])) {
                    $parts[] = (string) $filters[$key];
                }
            }

            return $this->joinUniqueTokens($parts);
        }

        return $this->joinUniqueTokens($this->collectLexicalTokens($question, $profile));
    }

    /**
     * @return list<string>
     */
    private function collectLexicalTokens(
        CanonicalScientificQuestion $question,
        ScientificSourceProfile $profile,
    ): array {
        $tokens = [];

        if ($profile->supportsField('entity')) {
            $entity = $this->entityLexical($question);
            if ($entity !== null) {
                $tokens[] = $entity;
            }
        }
        if ($profile->supportsField('target')) {
            $target = $this->nullableTrim($question->target->normalizedKey)
                ?? $this->nullableTrim($question->target->surface);
            if ($target !== null) {
                $tokens[] = $target;
            }
        }
        if ($profile->supportsField('process')) {
            $process = $this->nullableTrim($question->process->normalized)
                ?? $this->nullableTrim($question->process->surface);
            if ($process !== null) {
                $tokens[] = $process;
            }
        }
        if ($profile->supportsField('property')) {
            $property = $this->nullableTrim($question->property->key)
                ?? $this->nullableTrim($question->property->surface);
            if ($property !== null) {
                $tokens[] = $property;
            }
        }
        if ($profile->supportsField('relation')
            && $question->relation->type !== CanonicalScientificQuestion::RELATION_NONE
            && $question->relation->type !== '') {
            $tokens[] = $question->relation->type;
        }
        if ($profile->supportsField('geography')) {
            $geo = $this->nullableTrim($question->geography->country)
                ?? $this->nullableTrim($question->geography->label);
            if ($geo !== null) {
                $tokens[] = $geo;
            }
        }
        if ($profile->supportsField('time') && $question->time->year !== null) {
            $tokens[] = (string) $question->time->year;
        }
        if ($profile->supportsField('context')) {
            $sense = $this->nullableTrim($question->context->scientificSense);
            if ($sense !== null) {
                $tokens[] = $sense;
            }
        }

        return $tokens;
    }

    /**
     * Entity lexical form only — never substitutes cropBinding for question entity.
     */
    private function entityLexical(CanonicalScientificQuestion $question): ?string
    {
        return $this->nullableTrim($question->entity->normalized)
            ?? $this->nullableTrim($question->entity->surface)
            ?? $this->nullableTrim($question->entity->label);
    }

    /**
     * @param  list<string>  $parts
     */
    private function joinUniqueTokens(array $parts): string
    {
        $seen = [];
        $ordered = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '') {
                continue;
            }
            $key = mb_strtolower($trimmed);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $ordered[] = $trimmed;
        }

        return implode(' ', $ordered);
    }

    /**
     * @return array<string, mixed>
     */
    private function roleSnapshot(
        ?string $surface,
        ?string $normalized,
        string $resolution,
        ?string $canonicalId,
        ?string $canonicalNamespace,
    ): array {
        return [
            'surface' => $this->nullableTrim($surface),
            'normalized' => $this->nullableTrim($normalized),
            'resolution' => $resolution,
            'canonical_id' => $this->nullableTrim($canonicalId),
            'canonical_namespace' => $this->nullableTrim($canonicalNamespace),
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
