<?php

namespace App\Services\Agriculture\Intelligence\Contracts;

final class ProviderHealthState
{
    public const HEALTHY = 'healthy';

    public const DEGRADED = 'degraded';

    public const UNAVAILABLE = 'unavailable';

    public const NOT_CONFIGURED = 'not_configured';

    public const BLOCKED = 'blocked';

    public const UNKNOWN = 'unknown';
}
