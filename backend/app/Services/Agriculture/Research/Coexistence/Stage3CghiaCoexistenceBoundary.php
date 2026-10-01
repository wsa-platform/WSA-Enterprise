<?php

namespace App\Services\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Capability\CapabilityAccessMethod;
use App\Services\Agriculture\Research\Capability\CapabilityAndResultClass;
use App\Services\Agriculture\Research\Capability\CapabilityDimension;
use App\Services\Agriculture\Research\Capability\CapabilityRecord;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementEvaluator;
use App\Services\Agriculture\Research\Capability\CapabilityRequirementSet;
use App\Services\Agriculture\Research\Capability\CapabilitySubject;
use App\Services\Agriculture\Research\Correlation\CorrelationChain;
use App\Services\Agriculture\Research\Correlation\Persistence\DurableCorrelationRepository;
use App\Services\Agriculture\Research\Correlation\QuestionIdentityReference;
use App\Services\Agriculture\Research\Disclosure\C9ComposerFidelityDisclosureMapper;
use App\Services\Agriculture\Research\Path\CapabilityToPathEligibilityMapper;
use App\Services\Agriculture\Research\Path\PathDecisionId;
use App\Services\Agriculture\Research\Path\PathEligibilityState;
use App\Services\Agriculture\Research\Path\PathFallbackLayer;
use App\Services\Agriculture\Research\Path\PathFamily;
use App\Services\Agriculture\Research\Path\PathId;
use App\Services\Agriculture\Research\Path\PathLimitation;
use App\Services\Agriculture\Research\Path\PathModelDomainContract;
use App\Services\Agriculture\Research\Path\PathStatus;
use App\Services\Agriculture\Research\Projection\CanonicalQueryId;
use App\Services\Agriculture\Research\Projection\ProjectionEnvelope;
use App\Services\Agriculture\Research\Projection\ProjectionFacetAccounting;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityAggregator;
use App\Services\Agriculture\Research\Projection\ProjectionFidelityClass;
use App\Services\Agriculture\Research\Projection\ProjectionIdentity;
use App\Services\Agriculture\Research\Projection\ProjectionWarning;

/**
 * IU-09 Stage-3 ↔ CGHIA coexistence arbitration facade.
 *
 * Owns Selector↔Path arbitration only. Consumes IU-01…IU-08 without redesign.
 */
final class Stage3CghiaCoexistenceBoundary
{
    private readonly CoexistenceDisclosureContext $disclosureContext;

    public function __construct(
        private readonly Stage3SourceKeyIdentityBridge $identityBridge = new Stage3SourceKeyIdentityBridge(),
        private readonly CapabilityRequirementEvaluator $capabilityEvaluator = new CapabilityRequirementEvaluator(),
        private readonly CapabilityToPathEligibilityMapper $pathEligibilityMapper = new CapabilityToPathEligibilityMapper(),
        private readonly C9ComposerFidelityDisclosureMapper $disclosureMapper = new C9ComposerFidelityDisclosureMapper(),
        private readonly ?DurableCorrelationRepository $durableCorrelationRepository = null,
        ?CoexistenceDisclosureContext $disclosureContext = null,
    ) {
        $this->disclosureContext = $disclosureContext ?? CoexistenceDisclosureContext::instance();
    }

    /**
     * @param  list<string>  $selectorSourceKeys
     * @param  list<CapabilityRecord>  $capabilityRecords
     * @param  list<string>|null  $candidateSourceKeys  When null, uses selectorSourceKeys only
     */
    public function plan(
        CoexistenceMode $mode,
        array $selectorSourceKeys,
        string $sourceQuery,
        string $questionIdentity,
        string $csqIdentity,
        array $capabilityRecords = [],
        ?array $candidateSourceKeys = null,
        ?ProjectionFacetAccounting $facetAccounting = null,
    ): CoexistenceRetrievalPlan {
        $selectorKeys = array_values(array_unique(array_map(
            static fn (string $k): string => strtolower(trim($k)),
            $selectorSourceKeys,
        )));

        if ($mode === CoexistenceMode::LEGACY_ONLY) {
            $entries = [];
            foreach ($selectorKeys as $key) {
                $identity = $this->identityBridge->resolve($key);
                $entries[] = new CoexistenceSourcePlanEntry(
                    sourceKey: $key,
                    identity: $identity,
                    selectorSelected: true,
                    retrievalPermitted: true,
                    legacyCompatible: true,
                    cghiaArtifactsAttached: false,
                    verifiedAutomationClaimed: false,
                    events: [CoexistenceEventCode::LEGACY_COMPATIBLE],
                    limitations: ['CGHIA coexistence mode=LEGACY_ONLY; Stage-3 proceeds unchanged.'],
                );
            }

            return new CoexistenceRetrievalPlan(
                $mode,
                $selectorKeys,
                $entries,
                $sourceQuery,
                $questionIdentity,
                $csqIdentity,
            );
        }

        $candidates = $candidateSourceKeys === null
            ? $selectorKeys
            : array_values(array_unique(array_map(
                static fn (string $k): string => strtolower(trim($k)),
                $candidateSourceKeys,
            )));

        $entries = [];
        foreach ($candidates as $key) {
            $selectorSelected = in_array($key, $selectorKeys, true);
            if (! $selectorSelected) {
                $identity = $this->identityBridge->resolve($key);
                $entries[] = new CoexistenceSourcePlanEntry(
                    sourceKey: $key,
                    identity: $identity,
                    selectorSelected: false,
                    retrievalPermitted: false,
                    legacyCompatible: true,
                    cghiaArtifactsAttached: false,
                    verifiedAutomationClaimed: false,
                    events: [CoexistenceEventCode::SELECTOR_NOT_SELECTED],
                    limitations: ['Selector did not select this sourceKey; IU-09 must not invent adapter invocation.'],
                );
                continue;
            }

            $entries[] = $this->planSelectedSource(
                $key,
                $sourceQuery,
                $questionIdentity,
                $csqIdentity,
                $capabilityRecords,
                $facetAccounting,
            );
        }

        $plan = new CoexistenceRetrievalPlan(
            $mode,
            $selectorKeys,
            $entries,
            $sourceQuery,
            $questionIdentity,
            $csqIdentity,
        );

        $this->disclosureContext->replace($plan->disclosureHandoffs());

        return $plan;
    }

    /**
     * @param  list<CapabilityRecord>  $capabilityRecords
     */
    private function planSelectedSource(
        string $sourceKey,
        string $sourceQuery,
        string $questionIdentity,
        string $csqIdentity,
        array $capabilityRecords,
        ?ProjectionFacetAccounting $facetAccounting,
    ): CoexistenceSourcePlanEntry {
        $identity = $this->identityBridge->resolve($sourceKey);

        if ($identity->bindingClass === CoexistenceBindingClass::UNRESOLVED) {
            return new CoexistenceSourcePlanEntry(
                sourceKey: $sourceKey,
                identity: $identity,
                selectorSelected: true,
                retrievalPermitted: true,
                legacyCompatible: true,
                cghiaArtifactsAttached: false,
                verifiedAutomationClaimed: false,
                events: [
                    CoexistenceEventCode::IDENTITY_UNRESOLVED,
                    CoexistenceEventCode::LEGACY_COMPATIBLE,
                ],
                limitations: ['Identity unresolved; Stage-3 legacy retrieval may continue without CGHIA claim.'],
            );
        }

        if ($identity->bindingClass === CoexistenceBindingClass::EXTERNAL_ONLY) {
            return new CoexistenceSourcePlanEntry(
                sourceKey: $sourceKey,
                identity: $identity,
                selectorSelected: true,
                retrievalPermitted: true,
                legacyCompatible: true,
                cghiaArtifactsAttached: false,
                verifiedAutomationClaimed: false,
                events: [
                    CoexistenceEventCode::EXTERNAL_ONLY_NO_ADR_BINDING,
                    CoexistenceEventCode::CAP_UNVERIFIED,
                    CoexistenceEventCode::PATH_DEFERRED,
                    CoexistenceEventCode::LEGACY_COMPATIBLE,
                ],
                limitations: [
                    'No ADR membership binding for Stage-3 sourceKey; EXTERNAL_ONLY.',
                    'Cap subject requires adr_id — Cap/Path/Projection/B7 objects not fabricated.',
                    'Missing Cap treated as UNVERIFIED; Path DEFERRED; legacy Stage-3 may continue.',
                ],
            );
        }

        // ADR_BOUND — full Cap → Path → Projection → B7 → C9 chain.
        return $this->planAdrBoundSource(
            $sourceKey,
            $identity,
            $sourceQuery,
            $questionIdentity,
            $csqIdentity,
            $capabilityRecords,
            $facetAccounting ?? ProjectionFacetAccounting::of([]),
        );
    }

    /**
     * @param  list<CapabilityRecord>  $capabilityRecords
     */
    private function planAdrBoundSource(
        string $sourceKey,
        Stage3SourceKeyResolution $identity,
        string $sourceQuery,
        string $questionIdentity,
        string $csqIdentity,
        array $capabilityRecords,
        ProjectionFacetAccounting $facetAccounting,
    ): CoexistenceSourcePlanEntry {
        $adrId = $identity->adrId;
        if ($adrId === null) {
            throw new CoexistenceInvariantViolation('ADR_BOUND resolution missing adr_id.');
        }

        $subject = CapabilitySubject::of($adrId, $identity->canonicalIdentityId);
        $requirements = CapabilityRequirementSet::of([
            CapabilityDimension::accessMethod(CapabilityAccessMethod::API),
        ]);

        $decidedAt = gmdate('c');
        // Deterministic Cap/Path/Projection identities for IU-07 idempotency (no wall-clock in id hash).
        $capDecisionId = 'cap-dec-'.sha1($sourceKey.'|'.$adrId->value.'|'.$csqIdentity);
        $capDecision = $this->capabilityEvaluator->evaluate(
            $subject,
            $requirements,
            $capabilityRecords,
            $capDecisionId,
            $decidedAt,
        );

        $eligibility = $this->pathEligibilityMapper->map($capDecision->andResultClass);
        $status = $this->pathEligibilityMapper->suggestedStatus($eligibility);
        $pathFamily = $this->pathFamilyForSourceKey($sourceKey);
        $pathId = PathId::fromString('path-'.$sourceKey.'-'.$pathFamily->value.'-'.$adrId->value);
        $pathDecisionId = PathDecisionId::fromString('path-dec-'.sha1($pathId->value.'|'.$capDecisionId));

        $limitations = [];
        $events = [CoexistenceEventCode::CGHIA_ATTACHED];

        if ($capDecision->andResultClass === CapabilityAndResultClass::HAS_UNVERIFIED) {
            $events[] = CoexistenceEventCode::CAP_UNVERIFIED;
            $events[] = CoexistenceEventCode::PATH_DEFERRED;
            $limitations[] = 'Capability missing/UNVERIFIED (AND); Path DEFERRED; verified automation not claimed.';
        } elseif ($capDecision->andResultClass === CapabilityAndResultClass::HAS_UNAVAILABLE) {
            $events[] = CoexistenceEventCode::CAP_UNAVAILABLE;
            $events[] = CoexistenceEventCode::PATH_INELIGIBLE;
            $limitations[] = 'Capability UNAVAILABLE; Path INELIGIBLE; no CGHIA verified success.';
        } elseif ($capDecision->andResultClass === CapabilityAndResultClass::HAS_PARTIAL) {
            $limitations[] = 'Capability PARTIAL; Path CONDITIONAL only.';
        }

        if ($eligibility === PathEligibilityState::INELIGIBLE) {
            $events[] = CoexistenceEventCode::PATH_INELIGIBLE;
        } elseif ($eligibility === PathEligibilityState::DEFERRED) {
            $events[] = CoexistenceEventCode::PATH_DEFERRED;
        }

        $pathDecision = PathModelDomainContract::bindFromCapabilityDecision(
            $pathDecisionId,
            $pathId,
            $pathFamily,
            $capDecision,
            $status,
            limitations: array_map(
                static fn (string $text): PathLimitation => PathLimitation::of('COEXISTENCE', $text),
                $limitations,
            ),
            decisionReasons: ['IU-09 coexistence Cap→Path bind'],
            decidedAt: $decidedAt,
            fallbackLayer: PathFallbackLayer::L2,
        );

        $verifiedClaim = $eligibility === PathEligibilityState::ELIGIBLE
            && $status === PathStatus::SELECTED
            && $capDecision->andResultClass === CapabilityAndResultClass::ALL_VERIFIED;

        $legacyCompatible = true;
        $retrievalPermitted = true; // Selector selected; Stage-3 legacy may continue
        $cghiaAttached = false;
        $projection = null;
        $correlation = null;
        $durable = null;
        $disclosure = null;

        if ($eligibility === PathEligibilityState::INELIGIBLE) {
            $limitations[] = 'Path INELIGIBLE — CGHIA success not claimed; legacy Stage-3 may continue.';

            return new CoexistenceSourcePlanEntry(
                sourceKey: $sourceKey,
                identity: $identity,
                selectorSelected: true,
                retrievalPermitted: $retrievalPermitted,
                legacyCompatible: $legacyCompatible,
                cghiaArtifactsAttached: false,
                verifiedAutomationClaimed: false,
                events: self::uniqueEvents($events),
                limitations: $limitations,
                capabilityDecision: $capDecision,
                pathDecision: $pathDecision,
                eligibilityState: $eligibility,
                pathStatus: $status,
            );
        }

        try {
            $canonicalQueryId = CanonicalQueryId::fromString($csqIdentity);
            $projectionIdentity = ProjectionIdentity::fromString(
                'proj-'.sha1($csqIdentity.'|'.$adrId->value.'|'.$pathId->value.'|'.$sourceKey)
            );

            $computedFidelity = ProjectionFidelityAggregator::aggregateIdentityCritical(
                $facetAccounting->records,
            );
            // Never silently upgrade: non-verified Cap/Path cannot claim EXACT even if facets are vacuous.
            $fidelity = $verifiedClaim
                ? $computedFidelity
                : ($computedFidelity === ProjectionFidelityClass::EXACT
                    ? ProjectionFidelityClass::UNRESOLVED
                    : $computedFidelity);

            // Cap/Path eligibility alone must not imply an unqualified exact scientific claim.
            if ($fidelity !== ProjectionFidelityClass::EXACT) {
                $verifiedClaim = false;
            }

            $warnings = [];
            if (! $verifiedClaim) {
                $warnings[] = ProjectionWarning::of(
                    'COEXISTENCE_NON_VERIFIED',
                    'Projection attached under non-verified Cap/Path; EXACT Cap-backed automation not claimed.',
                );
            }

            $projection = ProjectionEnvelope::create(
                $projectionIdentity,
                $canonicalQueryId,
                $capDecision,
                $pathDecision,
                $sourceQuery,
                $facetAccounting,
                fidelityClass: $fidelity,
                projectionWarnings: $warnings,
            );

            $disclosure = $this->disclosureMapper->map($projection);

            $correlation = CorrelationChain::create(
                QuestionIdentityReference::fromString($questionIdentity),
                $canonicalQueryId,
                $adrId,
                $capDecision,
                $pathId,
                $pathDecisionId,
                $projectionIdentity,
                $identity->canonicalIdentityId,
            );

            if ($this->durableCorrelationRepository !== null) {
                try {
                    $idempotency = 'b7:'.sha1(implode('|', $correlation->compositeCorrelationTokens()));
                    $durable = $this->durableCorrelationRepository->persist($correlation, $idempotency);
                } catch (\Throwable) {
                    $events[] = CoexistenceEventCode::CORRELATION_INVARIANT;
                    $limitations[] = 'Durable correlation persistence failed (NON-FATAL / OBSERVABILITY).';
                }
            }

            $cghiaAttached = true;
            $events[] = CoexistenceEventCode::LEGACY_COMPATIBLE;
        } catch (\Throwable $e) {
            $events[] = CoexistenceEventCode::PROJECTION_INVARIANT;
            $limitations[] = 'Projection/C9/B7 attach failed: '.$e->getMessage();
            $cghiaAttached = false;
            $verifiedClaim = false;
            $projection = null;
            $correlation = null;
            $disclosure = null;
            $durable = null;
        }

        return new CoexistenceSourcePlanEntry(
            sourceKey: $sourceKey,
            identity: $identity,
            selectorSelected: true,
            retrievalPermitted: $retrievalPermitted,
            legacyCompatible: $legacyCompatible,
            cghiaArtifactsAttached: $cghiaAttached,
            verifiedAutomationClaimed: $verifiedClaim,
            events: self::uniqueEvents($events),
            limitations: $limitations,
            capabilityDecision: $capDecision,
            pathDecision: $pathDecision,
            eligibilityState: $eligibility,
            pathStatus: $status,
            projection: $projection,
            correlation: $correlation,
            durableCorrelation: $durable,
            disclosureHandoff: $disclosure,
        );
    }

    private function pathFamilyForSourceKey(string $sourceKey): PathFamily
    {
        return match ($sourceKey) {
            'openalex' => PathFamily::P12,
            'crossref' => PathFamily::P11,
            'semantic_scholar' => PathFamily::P13,
            'consensus' => PathFamily::P12,
            'fao_stat' => PathFamily::P01,
            default => PathFamily::P12,
        };
    }

    /**
     * @param  list<CoexistenceEventCode>  $events
     * @return list<CoexistenceEventCode>
     */
    private static function uniqueEvents(array $events): array
    {
        $out = [];
        $seen = [];
        foreach ($events as $event) {
            if (isset($seen[$event->value])) {
                continue;
            }
            $seen[$event->value] = true;
            $out[] = $event;
        }

        return $out;
    }
}
