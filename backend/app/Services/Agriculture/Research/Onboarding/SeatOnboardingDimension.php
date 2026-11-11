<?php

namespace App\Services\Agriculture\Research\Onboarding;

/**
 * The evidence state of one onboarding dimension of one seat (identity, licence, pagination...).
 */
final readonly class SeatOnboardingDimension
{
    /**
     * @param  list<string>  $evidenceRefs  where the value was read (URLs or repository paths)
     */
    public function __construct(
        public OnboardingEvidenceStatus $status,
        public ?string $value,
        public array $evidenceRefs = [],
        public ?string $note = null,
    ) {
        if (! array_is_list($evidenceRefs)) {
            throw new OnboardingContractViolation('Dimension evidence references must be a list.');
        }
        foreach ($evidenceRefs as $ref) {
            if (! is_string($ref) || trim($ref) === '') {
                throw new OnboardingContractViolation('Dimension evidence references must be non-empty strings.');
            }
        }
        if ($status === OnboardingEvidenceStatus::VERIFIED && $evidenceRefs === []) {
            throw new OnboardingContractViolation('A VERIFIED dimension needs at least one evidence reference.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'value' => $this->value,
            'evidence_refs' => $this->evidenceRefs,
            'note' => $this->note,
        ];
    }
}
