<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Result of evaluating one registration for one operation. Denials carry a diagnostic reason:
 * the declared status, or one of the reason constants below.
 */
final readonly class ScientificSourceCapabilityDecision
{
    public const REASON_VERIFIED_CURRENT = 'verified_current';

    public const REASON_LEGACY_OPERATIONAL_BASIS = 'legacy_operational_basis';

    public const REASON_NOT_DECLARED = 'capability_not_declared';

    public const REASON_EVIDENCE_STALE = 'capability_evidence_stale';

    public function __construct(
        public string $sourceKey,
        public ScientificSourceOperation $operation,
        public bool $allowed,
        public string $reason,
        public ?ScientificSourceCapabilityStatus $status = null,
        public ?string $evidenceReference = null,
    ) {}

    /** @return array<string, string|bool|null> */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'operation' => $this->operation->value,
            'allowed' => $this->allowed,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'evidence_reference' => $this->evidenceReference,
        ];
    }
}
