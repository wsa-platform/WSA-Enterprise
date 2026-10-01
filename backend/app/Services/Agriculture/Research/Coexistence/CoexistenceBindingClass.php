<?php

namespace App\Services\Agriculture\Research\Coexistence;

/**
 * Stage-3 sourceKey → IU-01 binding class (IU-09 bridge result).
 */
enum CoexistenceBindingClass: string
{
    case EXTERNAL_ONLY = 'EXTERNAL_ONLY';
    case ADR_BOUND = 'ADR_BOUND';
    case UNRESOLVED = 'UNRESOLVED';
}
