<?php

namespace App\Services\Agriculture\Research\Coexistence;

/**
 * Immutable multi-source coexistence retrieval plan — arbitration result only.
 */
final readonly class CoexistenceRetrievalPlan
{
    /**
     * @param  list<CoexistenceSourcePlanEntry>  $entries
     * @param  list<string>  $selectorSourceKeys
     */
    public function __construct(
        public CoexistenceMode $mode,
        public array $selectorSourceKeys,
        public array $entries,
        public string $sourceQuery,
        public string $questionIdentity,
        public string $csqIdentity,
    ) {
        foreach ($entries as $entry) {
            if (! $entry instanceof CoexistenceSourcePlanEntry) {
                throw new CoexistenceInvariantViolation(
                    'CoexistenceRetrievalPlan entries must be CoexistenceSourcePlanEntry instances.'
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    public function permittedSourceKeys(): array
    {
        $keys = [];
        foreach ($this->entries as $entry) {
            if ($entry->retrievalPermitted) {
                $keys[] = $entry->sourceKey;
            }
        }

        return $keys;
    }

    /**
     * @return list<\App\Services\Agriculture\Research\Disclosure\FidelityDisclosureHandoff>
     */
    public function disclosureHandoffs(): array
    {
        $handoffs = [];
        foreach ($this->entries as $entry) {
            if ($entry->disclosureHandoff !== null) {
                $handoffs[] = $entry->disclosureHandoff;
            }
        }

        return $handoffs;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode->value,
            'selector_source_keys' => $this->selectorSourceKeys,
            'source_query' => $this->sourceQuery,
            'question_identity' => $this->questionIdentity,
            'csq_identity' => $this->csqIdentity,
            'permitted_source_keys' => $this->permittedSourceKeys(),
            'entries' => array_map(
                static fn (CoexistenceSourcePlanEntry $e): array => $e->toArray(),
                $this->entries,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toObservabilityFragment(): array
    {
        return [
            'cghia_coexistence' => $this->toArray(),
        ];
    }
}
