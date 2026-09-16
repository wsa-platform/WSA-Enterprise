<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

final class FaoStatReadinessState
{
    public const DISABLED = 'DISABLED';

    public const NOT_CONFIGURED = 'NOT_CONFIGURED';

    public const CONFIGURED = 'CONFIGURED';

    public const AUTHENTICATION_FAILURE = 'AUTHENTICATION_FAILURE';

    public const UPSTREAM_UNAVAILABLE = 'UPSTREAM_UNAVAILABLE';

    public const READY = 'READY';

    public const NOT_READY = 'NOT_READY';

    public const CIRCUIT_OPEN = 'CIRCUIT_OPEN';

    public const NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';
}
