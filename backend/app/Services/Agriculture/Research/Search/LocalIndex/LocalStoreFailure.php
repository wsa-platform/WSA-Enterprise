<?php

namespace App\Services\Agriculture\Research\Search\LocalIndex;

/**
 * The record store could not complete an operation; nothing was committed by it.
 *
 * reason is a stable code for reports and search outcomes. Messages never carry SQL,
 * connection details or record content.
 */
final class LocalStoreFailure extends \RuntimeException
{
    public const STORE_UNAVAILABLE = 'local_store_unavailable';

    public const RECORD_CORRUPT = 'local_store_record_corrupt';

    public const CONCURRENT_GENERATION = 'local_store_concurrent_generation';

    private function __construct(public readonly string $reason, string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function unavailable(string $operation, \Throwable $previous): self
    {
        $state = (string) $previous->getCode();

        return new self(self::STORE_UNAVAILABLE, "The record store failed during {$operation}".($state !== '' && $state !== '0' ? " (SQLSTATE {$state})" : '').'.', $previous);
    }

    public static function corrupt(string $sourceKey, string $detail): self
    {
        return new self(self::RECORD_CORRUPT, "A stored record of '{$sourceKey}' failed validation: {$detail}.");
    }

    public static function concurrentGeneration(string $sourceKey, int $base, int $current): self
    {
        return new self(
            self::CONCURRENT_GENERATION,
            "Generation of '{$sourceKey}' was staged against {$base} but {$current} is committed; discard and re-run.",
        );
    }
}
