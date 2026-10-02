<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\Identity\SourceIdentityDomainContract;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;

/**
 * Immutable IC decision snapshot (storage DTO).
 *
 * Persistence only — does not mint identity/SAME_AS, Cap/Path/D-10 authority, or Stage-3 adapters.
 */
final readonly class IntegrationClassificationRecord
{
    public const CURRENT_SCHEMA_VERSION = 1;

    /**
     * @param  list<string>  $accessModalityClaims
     * @param  list<string>  $evidenceReferences
     * @param  array<string, mixed>|null  $metadata
     */
    private function __construct(
        public IntegrationClassificationRecordId $persistenceRecordId,
        public AdrMembershipId $adrId,
        public ?CanonicalSourceIdentityId $canonicalIdentityId,
        public ?string $identityBindingRef,
        public ClassificationDecisionIdentity $classificationDecisionIdentity,
        public ClassificationStatus $classificationStatus,
        public array $accessModalityClaims,
        public IntegrationNature $integrationNature,
        public IntegrationBoundary $integrationBoundary,
        public ?string $protocolFamily,
        public bool $sourceSpecificRequirement,
        public ?string $sourceSpecificRationale,
        public ?string $pathFamilyHint,
        public ?string $existingAdapterReference,
        public ?string $externalDependencyReference,
        public array $evidenceReferences,
        public string $evidenceFingerprint,
        public ?string $licenseReference,
        public ?string $accessReference,
        public ?string $reuseReference,
        public ?string $rationale,
        public string $decisionActor,
        public string $verifiedAt,
        public string $decisionTimestamp,
        public IntegrationClassificationLifecycleState $lifecycleState,
        public int $schemaVersion,
        public string $idempotencyKey,
        public ?IntegrationClassificationRecordId $supersededBy,
        public ?array $metadata,
    ) {}

    /**
     * @param  list<string>  $accessModalityClaims
     * @param  list<string>|null  $evidenceReferences
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>
     */
    public static function draftAttributes(
        AdrMembershipId $adrId,
        ClassificationDecisionIdentity $classificationDecisionIdentity,
        ClassificationStatus $classificationStatus,
        array $accessModalityClaims,
        IntegrationNature $integrationNature,
        IntegrationBoundary $integrationBoundary,
        bool $sourceSpecificRequirement,
        string $evidenceFingerprint,
        string $idempotencyKey,
        string $decisionActor,
        string $verifiedAt,
        string $decisionTimestamp,
        ?CanonicalSourceIdentityId $canonicalIdentityId = null,
        ?string $identityBindingRef = null,
        ?string $protocolFamily = null,
        ?string $sourceSpecificRationale = null,
        ?string $pathFamilyHint = null,
        ?string $existingAdapterReference = null,
        ?string $externalDependencyReference = null,
        ?array $evidenceReferences = null,
        ?string $licenseReference = null,
        ?string $accessReference = null,
        ?string $reuseReference = null,
        ?string $rationale = null,
        ?array $metadata = null,
    ): array {
        if ($classificationStatus === ClassificationStatus::UNCLASSIFIED) {
            throw new IntegrationClassificationInvariantViolation(
                'Persisting an IC decision with classification_status=UNCLASSIFIED is forbidden; missing record implies UNCLASSIFIED.'
            );
        }

        $fingerprint = trim($evidenceFingerprint);
        if ($fingerprint === '') {
            throw new IntegrationClassificationInvariantViolation(
                'evidence_fingerprint must be a non-empty string.'
            );
        }

        $key = trim($idempotencyKey);
        if ($key === '') {
            throw new IntegrationClassificationInvariantViolation('idempotency_key must be a non-empty string.');
        }

        $actor = trim($decisionActor);
        if ($actor === '') {
            throw new IntegrationClassificationInvariantViolation('decision_actor must be a non-empty string.');
        }

        $verified = trim($verifiedAt);
        if ($verified === '') {
            throw new IntegrationClassificationInvariantViolation('verified_at must be a non-empty string.');
        }

        $timestamp = trim($decisionTimestamp);
        if ($timestamp === '') {
            throw new IntegrationClassificationInvariantViolation('decision_timestamp must be a non-empty string.');
        }

        SourceIdentityDomainContract::assertDistinctNamespaces(
            $adrId,
            $canonicalIdentityId,
            null,
            null,
        );

        if ($canonicalIdentityId !== null && $adrId->value === $canonicalIdentityId->value) {
            throw new IntegrationClassificationInvariantViolation(
                'adr_id must not equal canonical_identity_id; seats and canonicals are distinct namespaces.'
            );
        }

        $modalities = self::normalizeAccessModalityClaims($accessModalityClaims);
        $refs = self::normalizeEvidenceRefs($evidenceReferences);
        $bindingRef = self::normalizeOptionalOpaque($identityBindingRef);
        $protocol = self::normalizeOptionalOpaque($protocolFamily);
        $ssRationale = self::normalizeOptionalOpaque($sourceSpecificRationale);
        $pathHint = self::normalizeOptionalOpaque($pathFamilyHint);
        $adapterRef = self::normalizeOptionalOpaque($existingAdapterReference);
        $extDep = self::normalizeOptionalOpaque($externalDependencyReference);
        $license = self::normalizeOptionalOpaque($licenseReference);
        $access = self::normalizeOptionalOpaque($accessReference);
        $reuse = self::normalizeOptionalOpaque($reuseReference);
        $rationaleNorm = self::normalizeOptionalOpaque($rationale);

        if ($integrationNature === IntegrationNature::SOURCE_NATIVE
            && $integrationBoundary === IntegrationBoundary::EXTERNAL_AGGREGATOR
        ) {
            throw new IntegrationClassificationInvariantViolation(
                'SOURCE_NATIVE cannot be paired with EXTERNAL_AGGREGATOR boundary without native evidence redesign; aggregator ≠ native.'
            );
        }

        if ($metadata !== null) {
            IntegrationClassificationPersistenceContract::assertMetadataNonAuthoritative($metadata);
        }

        return [
            'adr_id' => $adrId->value,
            'canonical_identity_id' => $canonicalIdentityId?->value,
            'identity_binding_ref' => $bindingRef,
            'classification_decision_identity' => $classificationDecisionIdentity->value,
            'classification_status' => $classificationStatus->value,
            'access_modality_claims' => $modalities,
            'integration_nature' => $integrationNature->value,
            'integration_boundary' => $integrationBoundary->value,
            'protocol_family' => $protocol,
            'source_specific_requirement' => $sourceSpecificRequirement,
            'source_specific_rationale' => $ssRationale,
            'path_family_hint' => $pathHint,
            'existing_adapter_reference' => $adapterRef,
            'external_dependency_reference' => $extDep,
            'evidence_references' => $refs,
            'evidence_fingerprint' => $fingerprint,
            'license_reference' => $license,
            'access_reference' => $access,
            'reuse_reference' => $reuse,
            'rationale' => $rationaleNorm,
            'decision_actor' => $actor,
            'verified_at' => $verified,
            'decision_timestamp' => $timestamp,
            'lifecycle_state' => IntegrationClassificationLifecycleState::ACTIVE->value,
            'schema_version' => self::CURRENT_SCHEMA_VERSION,
            'idempotency_key' => $key,
            'superseded_by' => null,
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  list<string>|mixed  $accessModalityClaims
     * @param  list<string>|mixed  $evidenceReferences
     * @param  array<string, mixed>|null  $metadata
     */
    public static function fromPersistedRow(
        IntegrationClassificationRecordId $persistenceRecordId,
        string $adrId,
        ?string $canonicalIdentityId,
        ?string $identityBindingRef,
        string $classificationDecisionIdentity,
        string $classificationStatus,
        mixed $accessModalityClaims,
        string $integrationNature,
        string $integrationBoundary,
        ?string $protocolFamily,
        bool $sourceSpecificRequirement,
        ?string $sourceSpecificRationale,
        ?string $pathFamilyHint,
        ?string $existingAdapterReference,
        ?string $externalDependencyReference,
        mixed $evidenceReferences,
        string $evidenceFingerprint,
        ?string $licenseReference,
        ?string $accessReference,
        ?string $reuseReference,
        ?string $rationale,
        string $decisionActor,
        string $verifiedAt,
        string $decisionTimestamp,
        IntegrationClassificationLifecycleState $lifecycleState,
        int $schemaVersion,
        string $idempotencyKey,
        ?IntegrationClassificationRecordId $supersededBy,
        ?array $metadata,
    ): self {
        $adr = AdrMembershipId::fromString($adrId);
        $canonical = $canonicalIdentityId === null || trim($canonicalIdentityId) === ''
            ? null
            : CanonicalSourceIdentityId::fromString($canonicalIdentityId);

        SourceIdentityDomainContract::assertDistinctNamespaces($adr, $canonical, null, null);

        if ($canonical !== null && $adr->value === $canonical->value) {
            throw new IntegrationClassificationInvariantViolation(
                'adr_id must not equal canonical_identity_id; seats and canonicals are distinct namespaces.'
            );
        }

        if ($metadata !== null) {
            IntegrationClassificationPersistenceContract::assertMetadataNonAuthoritative($metadata);
        }

        return new self(
            $persistenceRecordId,
            $adr,
            $canonical,
            self::normalizeOptionalOpaque($identityBindingRef),
            ClassificationDecisionIdentity::fromString($classificationDecisionIdentity),
            ClassificationStatus::from($classificationStatus),
            self::normalizeAccessModalityClaims(is_array($accessModalityClaims) ? $accessModalityClaims : []),
            IntegrationNature::from($integrationNature),
            IntegrationBoundary::from($integrationBoundary),
            self::normalizeOptionalOpaque($protocolFamily),
            $sourceSpecificRequirement,
            self::normalizeOptionalOpaque($sourceSpecificRationale),
            self::normalizeOptionalOpaque($pathFamilyHint),
            self::normalizeOptionalOpaque($existingAdapterReference),
            self::normalizeOptionalOpaque($externalDependencyReference),
            self::normalizeEvidenceRefs(is_array($evidenceReferences) ? $evidenceReferences : null),
            trim($evidenceFingerprint),
            self::normalizeOptionalOpaque($licenseReference),
            self::normalizeOptionalOpaque($accessReference),
            self::normalizeOptionalOpaque($reuseReference),
            self::normalizeOptionalOpaque($rationale),
            trim($decisionActor),
            trim($verifiedAt),
            trim($decisionTimestamp),
            $lifecycleState,
            $schemaVersion,
            trim($idempotencyKey),
            $supersededBy,
            $metadata,
        );
    }

    /**
     * Logical classification when no persisted decision exists.
     */
    public static function missingImpliesUnclassified(): ClassificationStatus
    {
        return ClassificationStatus::UNCLASSIFIED;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'persistence_record_id' => $this->persistenceRecordId->value,
            'adr_id' => $this->adrId->value,
            'canonical_identity_id' => $this->canonicalIdentityId?->value,
            'identity_binding_ref' => $this->identityBindingRef,
            'classification_decision_identity' => $this->classificationDecisionIdentity->value,
            'classification_status' => $this->classificationStatus->value,
            'access_modality_claims' => $this->accessModalityClaims,
            'integration_nature' => $this->integrationNature->value,
            'integration_boundary' => $this->integrationBoundary->value,
            'protocol_family' => $this->protocolFamily,
            'source_specific_requirement' => $this->sourceSpecificRequirement,
            'source_specific_rationale' => $this->sourceSpecificRationale,
            'path_family_hint' => $this->pathFamilyHint,
            'existing_adapter_reference' => $this->existingAdapterReference,
            'external_dependency_reference' => $this->externalDependencyReference,
            'evidence_references' => $this->evidenceReferences,
            'evidence_fingerprint' => $this->evidenceFingerprint,
            'license_reference' => $this->licenseReference,
            'access_reference' => $this->accessReference,
            'reuse_reference' => $this->reuseReference,
            'rationale' => $this->rationale,
            'decision_actor' => $this->decisionActor,
            'verified_at' => $this->verifiedAt,
            'decision_timestamp' => $this->decisionTimestamp,
            'lifecycle_state' => $this->lifecycleState->value,
            'schema_version' => $this->schemaVersion,
            'idempotency_key' => $this->idempotencyKey,
            'superseded_by' => $this->supersededBy?->value,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * @param  list<mixed>  $claims
     * @return list<string>
     */
    private static function normalizeAccessModalityClaims(array $claims): array
    {
        $allowed = array_fill_keys(IntegrationClassificationPersistenceContract::ACCESS_MODALITY_CLAIMS, true);
        $out = [];
        foreach ($claims as $claim) {
            if (! is_string($claim)) {
                throw new IntegrationClassificationInvariantViolation(
                    'access_modality_claims must be a list of strings.'
                );
            }
            $trimmed = trim($claim);
            if ($trimmed === '' || ! isset($allowed[$trimmed])) {
                throw new IntegrationClassificationInvariantViolation(
                    "Unknown or empty access_modality_claim [{$trimmed}]."
                );
            }
            $out[] = $trimmed;
        }

        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);

        return $out;
    }

    /**
     * @param  list<mixed>|null  $refs
     * @return list<string>
     */
    private static function normalizeEvidenceRefs(?array $refs): array
    {
        if ($refs === null) {
            return [];
        }

        $out = [];
        foreach ($refs as $ref) {
            if (! is_string($ref)) {
                throw new IntegrationClassificationInvariantViolation(
                    'evidence_references must be a list of opaque strings.'
                );
            }
            $trimmed = trim($ref);
            if ($trimmed === '') {
                throw new IntegrationClassificationInvariantViolation(
                    'evidence_references entries must be non-empty strings.'
                );
            }
            $out[] = $trimmed;
        }

        return $out;
    }

    private static function normalizeOptionalOpaque(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
