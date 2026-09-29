<?php

namespace App\Services\Agriculture\Research\Synthesis;

use App\Services\Agriculture\Research\CanonicalScientificQuestion;
use JsonException;
use InvalidArgumentException;

/**
 * Deterministic identity serializer for R8 multi-answers.
 *
 * This class owns identity projection + canonical serialization + answer-id hashing.
 * It does not select answers, rank evidence, calculate confidence, or perform
 * scientific inference. Crop binding is intentionally excluded from v1 because
 * the current CSQ does not distinguish scientific crop identity from UI context.
 */
final class MultiAnswerIdentitySerializer
{
    private const VERSION = 'multi-answer-id-v1';

    /**
     * @param list<string> $evidenceIds
     */
    public function serialize(
        CanonicalScientificQuestion $question,
        string $canonicalAnswer,
        array $evidenceIds,
    ): string {
        $payload = [
            'version' => self::VERSION,
            'question_identity' => [
                'entity' => $this->roleIdentity($question->entity->canonicalId, $question->entity->canonicalNamespace, $question->entity->normalized, $question->entity->surface),
                'target' => $this->roleIdentity($question->target->canonicalId, $question->target->canonicalNamespace, $question->target->normalizedKey, $question->target->surface),
                'process' => $this->roleIdentity($question->process->canonicalId, $question->process->canonicalNamespace, $question->process->normalized, $question->process->surface),
                'property' => [
                    'key' => $this->nullableTrim($question->property->key),
                    'surface' => $this->nullableTrim($question->property->surface),
                    'of_role' => $this->nullableTrim($question->property->ofRole),
                    'unit' => $this->nullableTrim($question->property->unit),
                ],
                'relation' => [
                    'type' => $this->nullableTrim($question->relation->type),
                    'from' => $this->nullableTrim($question->relation->from),
                    'to' => $this->nullableTrim($question->relation->to),
                    'state' => $this->nullableTrim($question->relation->state),
                    'operands' => array_map(
                        fn (array $operand): array => [
                            'surface' => $this->requiredTrimmedString($operand['surface'] ?? null, 'relation operand surface'),
                            'normalized' => $this->nullableTrim($operand['normalized'] ?? null),
                            'resolution' => $this->nullableTrim($operand['resolution'] ?? null),
                        ],
                        $question->relation->operands,
                    ),
                ],
                'scientific_sense' => $this->nullableTrim($question->context->scientificSense),
                'question_type' => $this->nullableTrim($question->context->questionType),
                'requested_information' => array_map(
                    fn (string $value): string => $this->requiredTrimmedString($value, 'requested information'),
                    $question->context->requestedInformation,
                ),
                'conditions' => array_map(
                    fn (array $condition): array => [
                        'type' => $this->requiredTrimmedString($condition['type'] ?? null, 'condition type'),
                        'value' => $this->nullableTrim($condition['value'] ?? null),
                        'label' => $this->nullableTrim($condition['label'] ?? null),
                    ],
                    $question->conditions->items,
                ),
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
            ],
            'canonical_answer' => $this->requiredTrimmedString($canonicalAnswer, 'canonical answer'),
            'canonical_evidence_set' => $this->canonicalEvidenceSet($evidenceIds),
        ];

        try {
            return json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to serialize multi-answer identity.', 0, $exception);
        }
    }

    /**
     * @param list<string> $evidenceIds
     */
    public function answerId(
        CanonicalScientificQuestion $question,
        string $canonicalAnswer,
        array $evidenceIds,
    ): string {
        return 'sha256:'.hash('sha256', $this->serialize($question, $canonicalAnswer, $evidenceIds));
    }

    public static function version(): string
    {
        return self::VERSION;
    }

    private function roleIdentity(
        ?string $canonicalId,
        ?string $canonicalNamespace,
        ?string $normalized,
        ?string $surface,
    ): array {
        $id = $this->nullableTrim($canonicalId);
        $namespace = $this->nullableTrim($canonicalNamespace);
        if ($id !== null && $namespace !== null) {
            return [
                'mode' => 'canonical',
                'id' => $id,
                'namespace' => $namespace,
            ];
        }

        $normalized = $this->nullableTrim($normalized);
        if ($normalized !== null) {
            return [
                'mode' => 'normalized',
                'value' => $normalized,
            ];
        }

        $surface = $this->nullableTrim($surface);
        if ($surface !== null) {
            return [
                'mode' => 'surface',
                'value' => $surface,
            ];
        }

        throw new InvalidArgumentException('Identity role must provide canonical, normalized, or surface identity.');
    }

    /**
     * @param list<string> $evidenceIds
     * @return list<string>
     */
    private function canonicalEvidenceSet(array $evidenceIds): array
    {
        $ids = [];
        foreach ($evidenceIds as $evidenceId) {
            if (! is_string($evidenceId)) {
                throw new InvalidArgumentException('Evidence IDs must be strings.');
            }
            $trimmed = trim($evidenceId);
            if ($trimmed === '') {
                throw new InvalidArgumentException('Evidence IDs must not be empty.');
            }
            $ids[$trimmed] = true;
        }

        $canonical = array_keys($ids);
        sort($canonical, SORT_STRING);

        return $canonical;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! is_string($value)) {
            throw new InvalidArgumentException('Identity string fields must be strings or null.');
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function requiredTrimmedString(mixed $value, string $field): string
    {
        $trimmed = $this->nullableTrim($value);
        if ($trimmed === null) {
            throw new InvalidArgumentException($field.' must not be empty.');
        }

        return $trimmed;
    }
}
