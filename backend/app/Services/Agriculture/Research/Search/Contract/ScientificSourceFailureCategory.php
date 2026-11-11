<?php

namespace App\Services\Agriculture\Research\Search\Contract;

/**
 * Typed category of an expected source failure (adapter contract v2, ADR-023 §8.36).
 *
 * Only expected failures of an external source carry a category. Programming defects and
 * test-framework signals never do: they propagate instead of becoming a source outcome.
 */
enum ScientificSourceFailureCategory: string
{
    case TRANSPORT = 'transport';
    case TIMEOUT = 'timeout';
    case RATE_LIMITED = 'rate_limited';
    case UPSTREAM_UNAVAILABLE = 'upstream_unavailable';
    case REQUEST_REJECTED = 'request_rejected';
    case AUTHENTICATION = 'authentication';
    case NOT_CONFIGURED = 'not_configured';
    case POLICY_DENIED = 'policy_denied';
    case CIRCUIT_OPEN = 'circuit_open';
    case MALFORMED_RESPONSE = 'malformed_response';
    case BUDGET_EXHAUSTED = 'budget_exhausted';
    case INVALID_REQUEST = 'invalid_request';
    case SOURCE = 'source';
    case UNCLASSIFIED = 'unclassified';

    /** Whether a later attempt of the same request can succeed without any change to it. */
    public function isRetryable(): bool
    {
        return match ($this) {
            self::TRANSPORT, self::TIMEOUT, self::RATE_LIMITED, self::UPSTREAM_UNAVAILABLE => true,
            default => false,
        };
    }
}
