<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Governance lifecycle of a runtime registration.
 *
 * LEGACY_STAGE3 covers the Stage-3 sources that predate ADR-023 membership. They are
 * EXTERNAL_ONLY (no `adr_id`, no SAME_AS) and keep their behaviour under D-10.7.5.
 * GOVERNED_ONBOARDED covers ADR seats that hold an accepted ACTIVE Onboarding Contract
 * (ADR-023 D-10.11) and are bound to a Stage-3 sourceKey.
 */
enum ScientificSourceLifecycle: string
{
    case LEGACY_STAGE3 = 'legacy_stage3';

    case GOVERNED_ONBOARDED = 'governed_onboarded';
}
