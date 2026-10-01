<?php

namespace App\Services\Agriculture\Research\Correlation;

use App\Services\Agriculture\Research\Capability\CapabilityDecisionIdentity;
use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;

/**
 * Immutable B7 correlation chain — linkage of existing identities by reference.
 *
 * Owns correlation only. Does not mint Identity/Cap/Path/Projection, mutate CSQ,
 * score evidence, classify C9, or persist.
 */
final readonly class CorrelationChain
{
    private function __construct(
        public QuestionIdentityReference $questionIdentity,
        public CanonicalQueryId $csqIdentity,
        public ?VariantIdentityReference $variantId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public string $capabilityDecisionId,
        public PathId $pathId,
        public PathDecisionId $pathDecisionId,
        public ProjectionIdentity $projectionIdentity,
        public ?RetrievalEventReference $retrievalEvent,
        public ?SourceRecordReference $originalSourceIdentifier,
        public ?AggregatorRecordReference $aggregatorRecordIdentifier,
    ) {}

    public static function create(
        QuestionIdentityReference $questionIdentity,
        CanonicalQueryId $csqIdentity,
        AdrMembershipId $adrId,
        CapabilityDecisionIdentity $capabilityDecision,
        PathId $pathId,
        PathDecisionId $pathDecisionId,
        ProjectionIdentity $projectionIdentity,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?VariantIdentityReference $variantId = null,
        ?RetrievalEventReference $retrievalEvent = null,
        ?SourceRecordReference $originalSourceIdentifier = null,
        ?AggregatorRecordReference $aggregatorRecordIdentifier = null,
    ): self {
        if (! $capabilityDecision->subject->adrId->equals($adrId)) {
            throw new CorrelationInvariantViolation(
                'Correlation adr_id must match capability_decision_identity subject adr_id.'
            );
        }

        $capCanonical = $capabilityDecision->subject->canonicalIdentityId?->value;
        $chainCanonical = $canonicalIdentityId?->value;
        if ($capCanonical !== $chainCanonical) {
            throw new CorrelationInvariantViolation(
                'Correlation canonical_identity_id must match capability_decision_identity subject canonical.'
            );
        }

        CorrelationDomainContract::assertDistinctLinkage(
            $questionIdentity,
            $csqIdentity,
            $variantId,
            $adrId,
            $canonicalIdentityId,
            $capabilityDecision->decisionId,
            $pathId,
            $pathDecisionId,
            $projectionIdentity,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
        );

        return new self(
            $questionIdentity,
            $csqIdentity,
            $variantId,
            $adrId,
            $canonicalIdentityId,
            $capabilityDecision->decisionId,
            $pathId,
            $pathDecisionId,
            $projectionIdentity,
            $retrievalEvent,
            $originalSourceIdentifier,
            $aggregatorRecordIdentifier,
        );
    }

    /**
     * Assembled correlation keys — not a canonical source identity.
     *
     * @return list<string>
     */
    public function compositeCorrelationTokens(): array
    {
        $tokens = [
            $this->questionIdentity->value,
            $this->csqIdentity->value,
            $this->adrId->value,
            $this->capabilityDecisionId,
            $this->pathId->value,
            $this->pathDecisionId->value,
            $this->projectionIdentity->value,
        ];
        if ($this->variantId !== null) {
            $tokens[] = $this->variantId->value;
        }
        if ($this->canonicalIdentityId !== null) {
            $tokens[] = $this->canonicalIdentityId->value;
        }

        return $tokens;
    }

    /**
     * Explicit: composite linkage is not a canonical source identity.
     */
    public function isCanonicalSourceIdentity(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question_identity' => $this->questionIdentity->value,
            'csq_identity' => $this->csqIdentity->value,
            'variant_id' => $this->variantId?->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'capability_decision_identity' => $this->capabilityDecisionId,
            'path_id' => $this->pathId->value,
            'path_decision_identity' => $this->pathDecisionId->value,
            'projection_identity' => $this->projectionIdentity->value,
            'retrieval_timestamp' => $this->retrievalEvent?->retrievedAt,
            'original_source_identifier' => $this->originalSourceIdentifier?->value,
            'aggregator_record_identifier' => $this->aggregatorRecordIdentifier?->value,
            'composite_is_canonical_source_identity' => false,
        ];
    }
}
