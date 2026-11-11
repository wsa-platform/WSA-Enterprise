<?php

namespace App\Services\Agriculture\Research\Search;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceFailureCategory;

/**
 * An expected failure of an external scientific source (for example an unusable payload).
 *
 * Adapters may throw it; the orchestrator records it as a failed outcome for that source.
 * Defects and test-framework signals must not be wrapped in it.
 */
final class ScientificSourceFailure extends \RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ScientificSourceFailureCategory $category = ScientificSourceFailureCategory::SOURCE,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
