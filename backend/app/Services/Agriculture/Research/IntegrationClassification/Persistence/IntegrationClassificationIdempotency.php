<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

/**
 * Deterministic SHA-256 idempotency for IC decisions (ADR-023 §8.14).
 *
 * Canonical byte rules (implementation encoding):
 * - UTF-8 JSON object with fixed key order
 * - JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
 * - access_modality_claims sorted as strings
 * - null protocol_family / path_family_hint encoded as JSON null
 * - source_specific_requirement as JSON boolean
 * - excludes: persistence id, wall-clock, lifecycle, actor display name, free-text rationale
 */
final class IntegrationClassificationIdempotency
{
    /**
     * @param  list<string>  $accessModalityClaims
     */
    public static function computeKey(
        string $adrId,
        string $evidenceFingerprint,
        array $accessModalityClaims,
        string $integrationNature,
        string $integrationBoundary,
        ?string $protocolFamily,
        bool $sourceSpecificRequirement,
        ?string $pathFamilyHint,
        string $actorPolicyVersion,
    ): string {
        $modalities = array_values($accessModalityClaims);
        sort($modalities, SORT_STRING);

        $payload = [
            'adr_id' => $adrId,
            'actor_policy_version' => $actorPolicyVersion,
            'dimension_claim_set' => [
                'access_modality_claims' => $modalities,
                'integration_boundary' => $integrationBoundary,
                'integration_nature' => $integrationNature,
                'path_family_hint' => $pathFamilyHint,
                'protocol_family' => $protocolFamily,
                'source_specific_requirement' => $sourceSpecificRequirement,
            ],
            'evidence_fingerprint' => $evidenceFingerprint,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $json);
    }
}
