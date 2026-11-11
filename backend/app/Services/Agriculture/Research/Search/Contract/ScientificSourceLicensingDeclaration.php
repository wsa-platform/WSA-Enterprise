<?php

namespace App\Services\Agriculture\Research\Search\Contract;

/**
 * Licensing and usage-rights declaration of one source (adapter contract v2, ADR-023 §8.36).
 *
 * Unknown is not permission: every usage not declared is UNRESOLVED, and only PERMITTED
 * permits. A licence identifier or any resolved usage requires the terms reference and the
 * evidence it was read from; nothing here may be inferred from a provider's popularity.
 */
final readonly class ScientificSourceLicensingDeclaration
{
    /** @var array<string, ScientificSourceUsageStatus> keyed by ScientificSourceUsage value */
    public array $usages;

    /**
     * @param  array<string, ScientificSourceUsageStatus>  $usages  keyed by ScientificSourceUsage value
     */
    public function __construct(
        array $usages = [],
        public ?string $licenceIdentifier = null,
        public ?string $termsReference = null,
        public ?string $evidenceReference = null,
    ) {
        $resolved = $licenceIdentifier !== null;
        $all = [];
        foreach (ScientificSourceUsage::cases() as $usage) {
            $all[$usage->value] = ScientificSourceUsageStatus::UNRESOLVED;
        }
        foreach ($usages as $key => $status) {
            if (! is_string($key) || ScientificSourceUsage::tryFrom($key) === null) {
                throw new ScientificSourceContractViolation("Unknown source usage '{$key}'.");
            }
            if (! $status instanceof ScientificSourceUsageStatus) {
                throw new ScientificSourceContractViolation("Usage '{$key}' must be a ScientificSourceUsageStatus.");
            }
            $all[$key] = $status;
            $resolved = $resolved || $status !== ScientificSourceUsageStatus::UNRESOLVED;
        }
        foreach (['licenceIdentifier' => $licenceIdentifier, 'termsReference' => $termsReference, 'evidenceReference' => $evidenceReference] as $field => $value) {
            if ($value !== null && trim($value) === '') {
                throw new ScientificSourceContractViolation("Licensing {$field} must be null or non-empty.");
            }
        }
        if ($resolved && ($termsReference === null || $evidenceReference === null)) {
            throw new ScientificSourceContractViolation(
                'A licence identifier or a resolved usage requires both a terms reference and an evidence reference.'
            );
        }
        $this->usages = $all;
    }

    /** Nothing is known: every usage is UNRESOLVED and nothing is permitted. */
    public static function unresolved(): self
    {
        return new self;
    }

    public function statusOf(ScientificSourceUsage $usage): ScientificSourceUsageStatus
    {
        return $this->usages[$usage->value];
    }

    /** Only an explicit PERMITTED permits; CONDITIONAL needs its conditions checked by the caller. */
    public function permits(ScientificSourceUsage $usage): bool
    {
        return $this->statusOf($usage) === ScientificSourceUsageStatus::PERMITTED;
    }

    public function isFullyUnresolved(): bool
    {
        foreach ($this->usages as $status) {
            if ($status !== ScientificSourceUsageStatus::UNRESOLVED) {
                return false;
            }
        }

        return $this->licenceIdentifier === null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'licence_identifier' => $this->licenceIdentifier,
            'terms_reference' => $this->termsReference,
            'evidence_reference' => $this->evidenceReference,
            'usages' => array_map(static fn (ScientificSourceUsageStatus $s): string => $s->value, $this->usages),
        ];
    }
}
