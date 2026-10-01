<?php

namespace App\Services\Agriculture\Research\Correlation\Persistence;

/**
 * Persistence lifecycle for Durable Correlation records (storage control only).
 */
enum DurableCorrelationLifecycleState: string
{
    case ACTIVE = 'ACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
    case INVALIDATED = 'INVALIDATED';
}
