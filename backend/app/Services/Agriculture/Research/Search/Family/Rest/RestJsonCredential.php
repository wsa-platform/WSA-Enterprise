<?php

namespace App\Services\Agriculture\Research\Search\Family\Rest;

use App\Services\Agriculture\Research\Search\Contract\ScientificSourceContractViolation;

/**
 * Where a REST source's credential is read from and how it is sent (ADR-023 §8.39).
 *
 * Holds the configuration key only. The value is read at request time and is never logged,
 * exported, or placed in an outcome.
 */
final readonly class RestJsonCredential
{
    public const PLACEMENT_HEADER = 'header';

    public const PLACEMENT_QUERY = 'query';

    public function __construct(
        public string $configKey,
        public string $placement,
        public string $name,
    ) {
        if (preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $configKey) !== 1) {
            throw new ScientificSourceContractViolation("Credential configKey must be a dotted config path; got '{$configKey}'.");
        }
        if (! in_array($placement, [self::PLACEMENT_HEADER, self::PLACEMENT_QUERY], true)) {
            throw new ScientificSourceContractViolation("Credential placement must be 'header' or 'query'; got '{$placement}'.");
        }
        if (preg_match('/^[A-Za-z0-9_-]+$/', $name) !== 1) {
            throw new ScientificSourceContractViolation("Credential name must be a header or parameter name; got '{$name}'.");
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'config_key' => $this->configKey,
            'placement' => $this->placement,
            'name' => $this->name,
        ];
    }
}
