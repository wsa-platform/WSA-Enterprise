<?php

namespace App\Services\Agriculture\Research\Search\Contract;

use App\Services\Agriculture\Intelligence\Adapters\Scientific\FaoStat\FaoStatErrorCategory;
use App\Services\Agriculture\Research\Search\ScientificSourceFailure;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Process\Exceptions\ProcessFailedException;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessExceptionInterface;

/**
 * The single definition of which failures are expected source failures (ADR-023 §8.36).
 *
 * Anything this class does not classify is a defect or a test-framework signal and must
 * propagate on both the sequential and the concurrent execution path.
 */
final class ScientificSourceFailureClassifier
{
    /** Message prefix of Laravel's ProcessDriver when a child process exits non-zero. */
    public const PROCESS_EXIT_FAILURE_PREFIX = 'Concurrent process failed with exit code [';

    /** Category of an expected source failure, or null when the throwable must propagate. */
    public static function classifyThrowable(\Throwable $throwable): ?ScientificSourceFailureCategory
    {
        return match (true) {
            $throwable instanceof ScientificSourceFailure => $throwable->category,
            $throwable instanceof ConnectionException => ScientificSourceFailureCategory::TRANSPORT,
            $throwable instanceof RequestException => self::categoryForHttpStatus($throwable->response->status()),
            $throwable instanceof TransferException => ScientificSourceFailureCategory::TRANSPORT,
            default => null,
        };
    }

    /**
     * v1 bridge: the category of a v1 outcome's error string; null when the outcome reports no failure.
     */
    public static function classifyOutcome(ScientificSourceSearchOutcome $outcome): ?ScientificSourceFailureCategory
    {
        $error = $outcome->error;
        if ($error === null || $error === '') {
            return in_array($outcome->status, [
                ScientificSourceSearchOutcome::STATUS_FAILED,
                ScientificSourceSearchOutcome::STATUS_UNAVAILABLE,
            ], true) ? ScientificSourceFailureCategory::UNCLASSIFIED : null;
        }

        return match ($error) {
            'request_exception' => ScientificSourceFailureCategory::TRANSPORT,
            'timeout' => ScientificSourceFailureCategory::TIMEOUT,
            'rate_limited' => ScientificSourceFailureCategory::RATE_LIMITED,
            'http_error' => self::categoryForHttpStatus($outcome->httpStatus),
            'provider_auth', 'provider_auth_or_billing' => ScientificSourceFailureCategory::AUTHENTICATION,
            'missing_api_key', 'unsupported_sources' => ScientificSourceFailureCategory::NOT_CONFIGURED,
            'malformed_or_unusable_results' => ScientificSourceFailureCategory::MALFORMED_RESPONSE,
            'time_budget_exhausted' => ScientificSourceFailureCategory::BUDGET_EXHAUSTED,
            'empty_query' => ScientificSourceFailureCategory::INVALID_REQUEST,
            'adapter_exception' => ScientificSourceFailureCategory::tryFrom(
                (string) ($outcome->observability['failure_category'] ?? '')
            ) ?? ScientificSourceFailureCategory::UNCLASSIFIED,
            FaoStatErrorCategory::EMPTY_RESULT => null,
            FaoStatErrorCategory::AUTHENTICATION_ERROR,
            FaoStatErrorCategory::AUTHORIZATION_ERROR,
            FaoStatErrorCategory::NOT_AUTHENTICATED => ScientificSourceFailureCategory::AUTHENTICATION,
            FaoStatErrorCategory::UPSTREAM_SERVER_ERROR => ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE,
            FaoStatErrorCategory::NETWORK_ERROR => ScientificSourceFailureCategory::TRANSPORT,
            FaoStatErrorCategory::TIMEOUT => ScientificSourceFailureCategory::TIMEOUT,
            FaoStatErrorCategory::UNKNOWN_UPSTREAM_ERROR => ScientificSourceFailureCategory::SOURCE,
            FaoStatErrorCategory::DISABLED,
            FaoStatErrorCategory::CONFIGURATION_ERROR,
            FaoStatErrorCategory::NOT_READY => ScientificSourceFailureCategory::NOT_CONFIGURED,
            FaoStatErrorCategory::DOMAIN_NOT_ALLOWED,
            FaoStatErrorCategory::DOMAIN_NOT_VERIFIED => ScientificSourceFailureCategory::POLICY_DENIED,
            FaoStatErrorCategory::CIRCUIT_OPEN => ScientificSourceFailureCategory::CIRCUIT_OPEN,
            FaoStatErrorCategory::INCOMPLETE_FILTERS,
            FaoStatErrorCategory::AMBIGUOUS_CODE,
            FaoStatErrorCategory::VALIDATION_ERROR => ScientificSourceFailureCategory::INVALID_REQUEST,
            default => ScientificSourceFailureCategory::UNCLASSIFIED,
        };
    }

    public static function categoryForHttpStatus(?int $status): ScientificSourceFailureCategory
    {
        return match (true) {
            $status === 429 => ScientificSourceFailureCategory::RATE_LIMITED,
            $status === 408 => ScientificSourceFailureCategory::TIMEOUT,
            in_array($status, [401, 402, 403], true) => ScientificSourceFailureCategory::AUTHENTICATION,
            $status !== null && $status >= 500 && $status <= 599 => ScientificSourceFailureCategory::UPSTREAM_UNAVAILABLE,
            $status !== null && $status >= 400 && $status <= 499 => ScientificSourceFailureCategory::REQUEST_REJECTED,
            default => ScientificSourceFailureCategory::UNCLASSIFIED,
        };
    }

    /**
     * Whether a throwable from the concurrency driver is a failure of the process pool itself.
     *
     * Laravel's ProcessDriver re-creates a child's exception by class name in the parent, so a
     * child defect arrives here as its own class and must propagate. Only process start, timeout
     * and signal failures, and the driver's non-zero exit report, are infrastructure failures.
     */
    public static function isConcurrencyInfrastructureFailure(\Throwable $throwable): bool
    {
        return $throwable instanceof ProcessExceptionInterface
            || $throwable instanceof ProcessFailedException
            || ($throwable::class === \Exception::class
                && str_starts_with($throwable->getMessage(), self::PROCESS_EXIT_FAILURE_PREFIX));
    }
}
