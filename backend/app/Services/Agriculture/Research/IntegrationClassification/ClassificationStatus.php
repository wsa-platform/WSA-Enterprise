<?php

namespace App\Services\Agriculture\Research\IntegrationClassification;

/**
 * IC classification_status — semantic authority axis (ADR-023 §8.13 / §8.14).
 *
 * Orthogonal to persistence lifecycle_state and to Cap/Path/D-10 states.
 * Missing IC record ⇒ UNCLASSIFIED (no automatic row).
 */
enum ClassificationStatus: string
{
    case CLASSIFIED = 'CLASSIFIED';
    case PARTIALLY_CLASSIFIED = 'PARTIALLY_CLASSIFIED';
    case UNCLASSIFIED = 'UNCLASSIFIED';
    case UNCLASSIFIABLE = 'UNCLASSIFIABLE';
    case NOT_APPLICABLE = 'NOT_APPLICABLE';
}
