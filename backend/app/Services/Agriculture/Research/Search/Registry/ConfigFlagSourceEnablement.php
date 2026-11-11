<?php

namespace App\Services\Agriculture\Research\Search\Registry;

final readonly class ConfigFlagSourceEnablement implements ScientificSourceEnablement
{
    public function __construct(
        public string $configKey,
        public bool $default,
    ) {}

    public function isEnabled(): bool
    {
        return filter_var(config($this->configKey, $this->default), FILTER_VALIDATE_BOOL);
    }
}
