<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * A local store, index or ingestion operation broke the storage lifecycle contract.
 */
final class LocalIndexInvariantViolation extends \LogicException {}
