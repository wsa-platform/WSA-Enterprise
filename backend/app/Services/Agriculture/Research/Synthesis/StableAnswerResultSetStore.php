<?php

namespace App\Services\Agriculture\Research\Synthesis;

use Illuminate\Support\Str;

/**
 * File-backed StableAnswerResultSet persistence (SQLite-compatible project; no new DB).
 *
 * Path: storage/app/scientific_stable_results/{organization_id}/{result_set_id}.json
 * Tenant isolation by organization_id directory + ownership check on read.
 */
final class StableAnswerResultSetStore
{
    public function __construct(
        private readonly ?string $rootPath = null,
    ) {}

    public function create(
        int $organizationId,
        string $originalQuestion,
        ?string $csqFingerprint,
        array $answers,
        array $references = [],
        array $observability = [],
        string $status = 'ready',
        int $ttlSeconds = StableAnswerResultSet::DEFAULT_TTL_SECONDS,
    ): StableAnswerResultSet {
        $id = 'srs_'.Str::lower((string) Str::ulid());
        $now = time();
        $set = new StableAnswerResultSet(
            resultSetId: $id,
            organizationId: $organizationId,
            originalQuestion: $originalQuestion,
            csqFingerprint: $csqFingerprint,
            answers: array_values($answers),
            references: array_values($references),
            createdAt: gmdate('c', $now),
            expiresAt: gmdate('c', $now + max(60, $ttlSeconds)),
            observability: $observability,
            status: $status,
        );
        $this->write($set);

        return $set;
    }

    public function get(int $organizationId, string $resultSetId): ?StableAnswerResultSet
    {
        $path = $this->pathFor($organizationId, $resultSetId);
        if (! is_file($path)) {
            return null;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return null;
        }
        $set = StableAnswerResultSet::fromArray($data);
        if ($set->organizationId !== $organizationId) {
            return null;
        }
        if ($set->isExpired()) {
            return null;
        }

        return $set;
    }

    private function write(StableAnswerResultSet $set): void
    {
        $dir = $this->dirFor($set->organizationId);
        if (! is_dir($dir)) {
            if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Unable to create stable result set directory.');
            }
        }
        $path = $this->pathFor($set->organizationId, $set->resultSetId);
        $payload = json_encode($set->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($payload === false) {
            throw new \RuntimeException('Unable to encode stable result set payload.');
        }
        if (file_put_contents($path, $payload) === false) {
            throw new \RuntimeException('Unable to persist stable result set file.');
        }
    }

    private function root(): string
    {
        return $this->rootPath ?? storage_path('app/scientific_stable_results');
    }

    private function dirFor(int $organizationId): string
    {
        return $this->root().DIRECTORY_SEPARATOR.$organizationId;
    }

    private function pathFor(int $organizationId, string $resultSetId): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $resultSetId) ?: 'invalid';

        return $this->dirFor($organizationId).DIRECTORY_SEPARATOR.$safe.'.json';
    }
}
