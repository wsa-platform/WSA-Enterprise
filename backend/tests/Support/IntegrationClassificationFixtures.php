<?php

namespace Tests\Support;

use App\Services\Agriculture\Research\Identity\AdrMembershipId;
use App\Services\Agriculture\Research\Identity\CanonicalSourceIdentityId;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationDecisionIdentity;
use App\Services\Agriculture\Research\IntegrationClassification\ClassificationStatus;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationBoundary;
use App\Services\Agriculture\Research\IntegrationClassification\IntegrationNature;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationLifecycleState;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationPersistenceContract;
use App\Services\Agriculture\Research\IntegrationClassification\Persistence\IntegrationClassificationRecord;
use Illuminate\Support\Facades\DB;

/**
 * Named-argument payloads for IC repository persist()/supersede() — synthetic identifiers only.
 */
trait IntegrationClassificationFixtures
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'adrId' => AdrMembershipId::fromString('ABSTRACT_IC_INTEGRITY'),
            'classificationDecisionIdentity' => ClassificationDecisionIdentity::fromString('ic-dec-integrity'),
            'classificationStatus' => ClassificationStatus::CLASSIFIED,
            'accessModalityClaims' => ['api', 'oai_pmh'],
            'integrationNature' => IntegrationNature::SOURCE_NATIVE,
            'integrationBoundary' => IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER,
            'sourceSpecificRequirement' => false,
            'evidenceFingerprint' => 'fp-integrity',
            'idempotencyKey' => 'ic:integrity',
            'decisionActor' => 'test-actor',
            'verifiedAt' => '2026-10-09T00:00:00Z',
            'decisionTimestamp' => '2026-10-09T00:00:00Z',
            'canonicalIdentityId' => CanonicalSourceIdentityId::fromString('cid_ic_integrity_1'),
            'identityBindingRef' => 'binding://integrity',
            'protocolFamily' => 'oai-pmh',
            'sourceSpecificRationale' => 'source-specific text',
            'pathFamilyHint' => 'P12',
            'existingAdapterReference' => 'adapter://integrity',
            'externalDependencyReference' => 'dependency://integrity',
            'evidenceReferences' => ['evidence://integrity/a', 'evidence://integrity/b'],
            'licenseReference' => 'license://integrity',
            'accessReference' => 'access://integrity',
            'reuseReference' => 'reuse://integrity',
            'rationale' => 'free text',
            'metadata' => ['note' => 'fixture'],
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function supersedePayload(IntegrationClassificationRecord $prior, array $overrides = []): array
    {
        $payload = $this->payload([
            'adrId' => $prior->adrId,
            'evidenceFingerprint' => 'fp-integrity-v2',
        ]);
        unset($payload['idempotencyKey']);

        return array_replace(
            ['priorId' => $prior->persistenceRecordId],
            $payload,
            ['newIdempotencyKey' => 'ic:integrity:v2'],
            $overrides,
        );
    }

    /**
     * Writes through the query builder: bypasses the repository and model events.
     */
    private function insertRawRow(string $adrId, string $idempotencyKey, IntegrationClassificationLifecycleState $lifecycle): void
    {
        DB::table(IntegrationClassificationPersistenceContract::TABLE)->insert([
            'adr_id' => $adrId,
            'canonical_identity_id' => null,
            'identity_binding_ref' => null,
            'classification_decision_identity' => 'ic-dec-raw-'.$idempotencyKey,
            'classification_status' => ClassificationStatus::CLASSIFIED->value,
            'access_modality_claims' => json_encode(['api']),
            'integration_nature' => IntegrationNature::SOURCE_NATIVE->value,
            'integration_boundary' => IntegrationBoundary::PROTOCOL_FAMILY_ADAPTER->value,
            'protocol_family' => null,
            'source_specific_requirement' => false,
            'source_specific_rationale' => null,
            'path_family_hint' => null,
            'existing_adapter_reference' => null,
            'external_dependency_reference' => null,
            'evidence_references' => json_encode([]),
            'evidence_fingerprint' => 'fp-raw-'.$idempotencyKey,
            'license_reference' => null,
            'access_reference' => null,
            'reuse_reference' => null,
            'rationale' => null,
            'decision_actor' => 'raw-fixture',
            'verified_at' => '2026-10-09T00:00:00Z',
            'decision_timestamp' => '2026-10-09T00:00:00Z',
            'lifecycle_state' => $lifecycle->value,
            'schema_version' => IntegrationClassificationRecord::CURRENT_SCHEMA_VERSION,
            'idempotency_key' => $idempotencyKey,
            'superseded_by' => null,
            'metadata' => null,
            'created_at' => '2026-10-09 00:00:00',
            'updated_at' => '2026-10-09 00:00:00',
        ]);
    }
}
