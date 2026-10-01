<?php

namespace App\Services\Agriculture\Research\Identity;

/**
 * External identity classification (not ADR membership, not Cap states, not Stage-3 keys).
 */
enum ExternalIdentityKind: string
{
    case EXTERNAL_DEPENDENCY = 'EXTERNAL_DEPENDENCY';
    case EXTERNAL_AGGREGATOR = 'EXTERNAL_AGGREGATOR';
    case EXTERNAL_RESOURCE = 'EXTERNAL_RESOURCE';
}
