<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * Orchestrator scheduling group of a registered source.
 */
enum ScientificSourceExecutionGroup: string
{
    /** Independent scholarly HTTP providers that Home generic research may overlap. */
    case INDEPENDENT_SCHOLARLY = 'independent_scholarly';

    /** Structured statistical providers scheduled by statistical need. */
    case STATISTICAL = 'statistical';

    /** Always executed sequentially. */
    case SEQUENTIAL = 'sequential';
}
