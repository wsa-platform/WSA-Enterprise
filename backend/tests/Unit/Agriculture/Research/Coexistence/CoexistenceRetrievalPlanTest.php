<?php

namespace Tests\Unit\Agriculture\Research\Coexistence;

use App\Services\Agriculture\Research\Coexistence\CoexistenceEventCode;
use App\Services\Agriculture\Research\Coexistence\CoexistenceMode;
use App\Services\Agriculture\Research\Coexistence\CoexistenceRetrievalPlan;
use App\Services\Agriculture\Research\Coexistence\CoexistenceSourcePlanEntry;
use App\Services\Agriculture\Research\Coexistence\Stage3SourceKeyIdentityBridge;
use PHPUnit\Framework\TestCase;

/**
 * IU-09 — CoexistenceRetrievalPlan DTO (result only, not an authority).
 */
final class CoexistenceRetrievalPlanTest extends TestCase
{
    public function test_plan_exposes_permitted_keys_and_observability_fragment(): void
    {
        $bridge = new Stage3SourceKeyIdentityBridge();
        $entry = new CoexistenceSourcePlanEntry(
            sourceKey: 'openalex',
            identity: $bridge->resolve('openalex'),
            selectorSelected: true,
            retrievalPermitted: true,
            legacyCompatible: true,
            cghiaArtifactsAttached: false,
            verifiedAutomationClaimed: false,
            events: [CoexistenceEventCode::LEGACY_COMPATIBLE],
            limitations: [],
        );

        $plan = new CoexistenceRetrievalPlan(
            CoexistenceMode::LEGACY_ONLY,
            ['openalex'],
            [$entry],
            'wheat yield',
            'q:test',
            'csq:test',
        );

        $this->assertSame(['openalex'], $plan->permittedSourceKeys());
        $fragment = $plan->toObservabilityFragment();
        $this->assertArrayHasKey('cghia_coexistence', $fragment);
        $this->assertSame('legacy_only', $fragment['cghia_coexistence']['mode']);
        $this->assertSame([], $plan->disclosureHandoffs());
    }

    public function test_event_taxonomy_keeps_empty_result_distinct_from_cap_unavailable(): void
    {
        $this->assertNotSame(
            CoexistenceEventCode::EMPTY_RESULT->value,
            CoexistenceEventCode::CAP_UNAVAILABLE->value,
        );
        $this->assertNotSame(
            CoexistenceEventCode::RUNTIME_FAILURE->value,
            CoexistenceEventCode::CAP_UNVERIFIED->value,
        );
        $this->assertNotSame(
            CoexistenceEventCode::RUNTIME_FAILURE->value,
            CoexistenceEventCode::CAP_UNAVAILABLE->value,
        );
    }
}
