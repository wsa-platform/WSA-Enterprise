<?php

namespace App\Services\Agriculture\Research\IntegrationClassification;

/**
 * IC integration nature vocabulary (ADR-023 §8.13 / §8.14).
 *
 * EXTERNAL_DEPENDENCY remains distinct from SOURCE_NATIVE — no silent native upgrade.
 */
enum IntegrationNature: string
{
    case SOURCE_NATIVE = 'SOURCE_NATIVE';
    case EXTERNAL_DEPENDENCY = 'EXTERNAL_DEPENDENCY';
    case MANUAL_ONLY = 'MANUAL_ONLY';
    case NATURE_UNVERIFIED = 'NATURE_UNVERIFIED';
}
