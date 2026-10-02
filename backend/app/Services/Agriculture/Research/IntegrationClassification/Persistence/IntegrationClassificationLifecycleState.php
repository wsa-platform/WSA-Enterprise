<?php

namespace App\Services\Agriculture\Research\IntegrationClassification\Persistence;

/**
 * Persistence lifecycle for IC decision records (storage control only).
 *
 * lifecycle ACTIVE ≠ D-10 ACTIVE.
 */
enum IntegrationClassificationLifecycleState: string
{
    case ACTIVE = 'ACTIVE';
    case SUPERSEDED = 'SUPERSEDED';
    case INVALIDATED = 'INVALIDATED';
}
