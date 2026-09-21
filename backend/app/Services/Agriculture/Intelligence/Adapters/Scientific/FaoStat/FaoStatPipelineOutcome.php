<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Internal FAOSTAT pipeline outcome taxonomy.
 * Distinguishes empty retrieval from rejection stages without a public API break.
 */
final class FaoStatPipelineOutcome
{
    public const SUCCESS = 'SUCCESS';

    public const INCOMPLETE_FILTERS = 'INCOMPLETE_FILTERS';

    public const AMBIGUOUS_MEASURES = 'AMBIGUOUS_MEASURES';

    public const MEASURE_CONFLICT = 'MEASURE_CONFLICT';

    public const DECOMPOSED_MEASURES = 'DECOMPOSED_MEASURES';

    public const EMPTY_RESULT = 'EMPTY_RESULT';

    public const REJECTED_FAO_GATE = 'REJECTED_FAO_GATE';

    public const REJECTED_ALIGNER = 'REJECTED_ALIGNER';

    public const REJECTED_DOWNSTREAM = 'REJECTED_DOWNSTREAM';

    public const PROVIDER_ERROR = 'PROVIDER_ERROR';

    public const NOT_SELECTED = 'NOT_SELECTED';

    public const DISABLED = 'DISABLED';
}
