<?php

namespace App\Services\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\ExternalIdentityRef;

/**
 * Immutable result of Stage-3 sourceKey → IU-01 namespace resolution.
 *
 * Not an identity authority — consumes IU-01 types only.
 */
final readonly class Stage3SourceKeyResolution
{
    private function __construct(
        public string $sourceKey,
        public CoexistenceBindingClass $bindingClass,
        public ?ExternalIdentityRef $externalRef,
        public ?AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
    ) {}

    public static function externalOnly(string $sourceKey, ExternalIdentityRef $externalRef): self
    {
        return new self(
            $sourceKey,
            CoexistenceBindingClass::EXTERNAL_ONLY,
            $externalRef,
            null,
            null,
        );
    }

    public static function adrBound(
        string $sourceKey,
        ExternalIdentityRef $externalRef,
        AdrMembershipId $adrId,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
    ): self {
        if ($adrId->value === $sourceKey) {
            throw new CoexistenceInvariantViolation(
                'adr_id must not equal Stage-3 sourceKey (no silent namespace collapse).'
            );
        }
        if ($canonicalIdentityId !== null && $canonicalIdentityId->value === $sourceKey) {
            throw new CoexistenceInvariantViolation(
                'canonical_identity_id must not equal Stage-3 sourceKey.'
            );
        }
        if ($canonicalIdentityId !== null && $canonicalIdentityId->value === $adrId->value) {
            throw new CoexistenceInvariantViolation(
                'canonical_identity_id must not equal adr_id.'
            );
        }

        return new self(
            $sourceKey,
            CoexistenceBindingClass::ADR_BOUND,
            $externalRef,
            $adrId,
            $canonicalIdentityId,
        );
    }

    public static function unresolved(string $sourceKey): self
    {
        return new self(
            $sourceKey,
            CoexistenceBindingClass::UNRESOLVED,
            null,
            null,
            null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_key' => $this->sourceKey,
            'binding_class' => $this->bindingClass->value,
            'external_ref' => $this->externalRef === null ? null : [
                'kind' => $this->externalRef->kind->value,
                'identifier' => $this->externalRef->identifier,
            ],
            'adr_id' => $this->adrId?->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
        ];
    }
}
