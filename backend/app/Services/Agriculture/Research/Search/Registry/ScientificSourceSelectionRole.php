<?php

namespace App\Services\Agriculture\Research\Search\Registry;

/**
 * How Stage-3 selection may use a registered source.
 */
enum ScientificSourceSelectionRole: string
{
    /** Auto-selected for Internet-First plans when enabled. */
    case INTERNET_FIRST_DEFAULT = 'internet_first_default';

    /** Auto-considered ahead of the defaults when enabled; consideration is not evidence acceptance. */
    case STATISTICAL_CONSIDERATION = 'statistical_consideration';

    /** Registered but never auto-selected; runs only when a caller names it. */
    case OPTIONAL_EXPLICIT_ONLY = 'optional_explicit_only';

    /**
     * Governed ADR seats activated after onboarding; auto-selected on Internet-First
     * plans without displacing the Stage-3 scholarly defaults.
     */
    case GOVERNED_EXPANSION = 'governed_expansion';
}
