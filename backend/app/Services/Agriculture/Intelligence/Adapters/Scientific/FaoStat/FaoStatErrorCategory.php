<?php

namespace App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat;

/**
 * WSA client-side error categories for FAOSTAT Developer Portal.
 * Not official FAOSTAT error codes.
 */
final class FaoStatErrorCategory
{
    public const AUTHENTICATION_ERROR = 'AUTHENTICATION_ERROR';

    public const AUTHORIZATION_ERROR = 'AUTHORIZATION_ERROR';

    public const EMPTY_RESULT = 'EMPTY_RESULT';

    public const UPSTREAM_SERVER_ERROR = 'UPSTREAM_SERVER_ERROR';

    public const NETWORK_ERROR = 'NETWORK_ERROR';

    public const TIMEOUT = 'TIMEOUT';

    public const UNKNOWN_UPSTREAM_ERROR = 'UNKNOWN_UPSTREAM_ERROR';

    public const DISABLED = 'DISABLED';

    public const DOMAIN_NOT_ALLOWED = 'DOMAIN_NOT_ALLOWED';

    public const DOMAIN_NOT_VERIFIED = 'DOMAIN_NOT_VERIFIED';

    public const INCOMPLETE_FILTERS = 'INCOMPLETE_FILTERS';

    public const AMBIGUOUS_CODE = 'AMBIGUOUS_CODE';

    public const CONFIGURATION_ERROR = 'CONFIGURATION_ERROR';

    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    public const CIRCUIT_OPEN = 'CIRCUIT_OPEN';

    public const NOT_READY = 'NOT_READY';

    public const NOT_AUTHENTICATED = 'NOT_AUTHENTICATED';
}
