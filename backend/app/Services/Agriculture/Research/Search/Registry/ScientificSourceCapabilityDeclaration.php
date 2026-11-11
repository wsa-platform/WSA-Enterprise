<?php

namespace App\Services\Agriculture\Research\Search\Registry;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * One reviewed, in-code capability declaration of a runtime registration (ADR-023 §8.35).
 *
 * The evidence reference names where the evidence is recorded; it is never inferred.
 * Verified declarations carry the verification time; a supported one also carries the
 * time after which its evidence is stale and stops authorizing the operation.
 */
final readonly class ScientificSourceCapabilityDeclaration
{
    public function __construct(
        public ScientificSourceOperation $operation,
        public ScientificSourceCapabilityStatus $status,
        public string $evidenceReference,
        public ?string $verifiedAt = null,
        public ?string $expiresAt = null,
    ) {
        $op = $operation->value;
        if (trim($evidenceReference) === '') {
            throw new ScientificSourceRegistryInvariantViolation(
                "Capability declaration for '{$op}' requires an evidence reference."
            );
        }
        if ($status === ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS
            && $operation !== ScientificSourceOperation::SEARCH) {
            throw new ScientificSourceRegistryInvariantViolation(
                "The legacy operational basis covers the search operation only; got '{$op}'."
            );
        }
        $verified = self::parseTime($verifiedAt, 'verifiedAt', $op);
        $expires = self::parseTime($expiresAt, 'expiresAt', $op);
        if ($status->isVerified() && $verified === null) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Verified capability '{$op}' requires verifiedAt."
            );
        }
        if ($status === ScientificSourceCapabilityStatus::VERIFIED_SUPPORTED
            && ($expires === null || $expires <= $verified)) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Supported capability '{$op}' requires an expiresAt later than verifiedAt."
            );
        }
    }

    public static function legacyOperationalBasis(string $evidenceReference): self
    {
        return new self(
            ScientificSourceOperation::SEARCH,
            ScientificSourceCapabilityStatus::LEGACY_OPERATIONAL_BASIS,
            $evidenceReference,
        );
    }

    public function isExpiredAt(DateTimeInterface $now): bool
    {
        $expires = self::parseTime($this->expiresAt, 'expiresAt', $this->operation->value);

        return $expires !== null && $now >= $expires;
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation->value,
            'status' => $this->status->value,
            'evidence_reference' => $this->evidenceReference,
            'verified_at' => $this->verifiedAt,
            'expires_at' => $this->expiresAt,
        ];
    }

    private static function parseTime(?string $value, string $field, string $op): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);
        if ($parsed === false || $parsed->format(DateTimeInterface::ATOM) !== $value) {
            throw new ScientificSourceRegistryInvariantViolation(
                "Capability '{$op}' {$field} must be an ISO-8601 timestamp with offset; got '{$value}'."
            );
        }

        return $parsed;
    }
}
