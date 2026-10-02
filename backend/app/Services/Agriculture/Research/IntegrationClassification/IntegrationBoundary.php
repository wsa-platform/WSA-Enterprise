<?php

namespace App\Services\Agriculture\Research\IntegrationClassification;

/**
 * IC integration boundary vocabulary (ADR-023 §8.13 / §8.14).
 *
 * STAGE3_ADAPTER is a classification claim only — not Stage-3 registration/activation.
 * FUTURE_ADAPTER_PLANNING_ONLY is GOVERNANCE-ONLY and cannot produce runtime eligibility.
 */
enum IntegrationBoundary: string
{
    case PROTOCOL_FAMILY_ADAPTER = 'PROTOCOL_FAMILY_ADAPTER';
    case SOURCE_SPECIFIC_ADAPTER = 'SOURCE_SPECIFIC_ADAPTER';
    case STAGE3_ADAPTER = 'STAGE3_ADAPTER';
    case EXTERNAL_AGGREGATOR = 'EXTERNAL_AGGREGATOR';
    case MANUAL_STATIC = 'MANUAL_STATIC';
    case FUTURE_ADAPTER_PLANNING_ONLY = 'FUTURE_ADAPTER_PLANNING_ONLY';
}
