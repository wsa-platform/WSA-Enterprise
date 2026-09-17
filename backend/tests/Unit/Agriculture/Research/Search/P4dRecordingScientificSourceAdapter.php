<?php

namespace Tests\Unit\Agriculture\Research\Search;

use App\Contracts\ScientificSourceAdapterInterface;
use App\Services\Agriculture\Research\Search\ScientificSearchTimeBudget;
use App\Services\Agriculture\Research\Search\ScientificSourceSearchOutcome;

/**
 * Serializable Stage 3 adapter double for P4d process-concurrency tests.
 * Lives in its own file so artisan child processes can autoload it.
 */
final class P4dRecordingScientificSourceAdapter implements ScientificSourceAdapterInterface
{
    private int $callIndex = 0;

    /**
     * @param  list<ScientificSourceSearchOutcome>  $outcomes
     */
    public function __construct(
        private string $key,
        private string $tracePath,
        private array $outcomes,
        private int $delayMicroseconds = 0,
    ) {}

    public function sourceKey(): string
    {
        return $this->key;
    }

    public function displayName(): string
    {
        return $this->key;
    }

    public function search(string $query, int $limit = 10, array $options = []): ScientificSourceSearchOutcome
    {
        $started = microtime(true);
        $this->trace('start', $started, $options, $query);

        if ($this->delayMicroseconds > 0) {
            usleep($this->delayMicroseconds);
        }

        $ended = microtime(true);
        $this->trace('end', $ended, $options, $query);

        $outcome = $this->outcomes[$this->callIndex] ?? $this->outcomes[array_key_last($this->outcomes)] ?? new ScientificSourceSearchOutcome(
            sourceKey: $this->key,
            status: ScientificSourceSearchOutcome::STATUS_EMPTY,
            error: 'no_scripted_outcome',
        );
        $this->callIndex++;

        return $outcome;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function trace(string $event, float $at, array $options, string $query): void
    {
        $live = ScientificSearchTimeBudget::current();
        $payload = json_encode([
            'key' => $this->key,
            'event' => $event,
            't' => $at,
            'query' => $query,
            'remaining_option' => $options['search_budget_remaining_seconds'] ?? null,
            'live_remaining' => $live?->remainingSeconds(),
            'budget_seconds' => $live?->seconds,
        ], JSON_THROW_ON_ERROR);

        file_put_contents($this->tracePath, $payload."\n", FILE_APPEND | LOCK_EX);
    }
}
