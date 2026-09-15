<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * Safe FAOSTAT portal exception — never includes tokens, credentials, or Authorization headers.
 */
final class FaoStatPortalException extends \RuntimeException
{
    public function __construct(
        public readonly string $category,
        string $safeMessage,
        public readonly ?int $httpStatus = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($safeMessage, 0, $previous);
    }
}
