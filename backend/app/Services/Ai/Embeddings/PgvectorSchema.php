<?php

namespace App\Services\Ai\Embeddings;

use App\Services\Ai\AiErrorSanitizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PgvectorSchema
{
    private ?bool $extensionAvailable = null;

    private ?bool $nativeColumnAvailable = null;

    private ?bool $hnswAvailable = null;

    public function extensionAvailable(): bool
    {
        if ($this->extensionAvailable !== null) {
            return $this->extensionAvailable;
        }
        if (! $this->isPgsql()) {
            return $this->extensionAvailable = false;
        }
        try {
            $row = DB::selectOne("SELECT 1 AS present FROM pg_extension WHERE extname = 'vector'");
            $this->extensionAvailable = $row !== null;
        } catch (\Throwable) {
            $this->extensionAvailable = false;
        }

        return $this->extensionAvailable;
    }

    public function nativeColumnAvailable(): bool
    {
        if ($this->nativeColumnAvailable !== null) {
            return $this->nativeColumnAvailable;
        }
        if (! $this->extensionAvailable()) {
            return $this->nativeColumnAvailable = false;
        }
        try {
            $this->nativeColumnAvailable = Schema::hasColumn('knowledge_embeddings', 'embedding_vector');
        } catch (\Throwable) {
            $this->nativeColumnAvailable = false;
        }

        return $this->nativeColumnAvailable;
    }

    public function hnswAvailable(): bool
    {
        if ($this->hnswAvailable !== null) {
            return $this->hnswAvailable;
        }
        if (! $this->nativeColumnAvailable()) {
            return $this->hnswAvailable = false;
        }
        try {
            $row = DB::selectOne(
                'SELECT 1 AS present FROM pg_class c JOIN pg_am a ON c.relam = a.oid WHERE c.relname = ? AND a.amname = ?',
                [PgvectorAnnQuery::INDEX_NAME, 'hnsw'],
            );
            $this->hnswAvailable = $row !== null;
        } catch (\Throwable) {
            $this->hnswAvailable = false;
        }

        return $this->hnswAvailable;
    }

    public function annReady(): bool
    {
        return $this->nativeColumnAvailable();
    }

    /**
     * Enable pgvector and attach the native vector column/index when the server supports it.
     * Returns false when the extension is not installed; does not corrupt existing JSON rows.
     */
    public function migrate(): bool
    {
        if (! $this->isPgsql()) {
            return false;
        }
        if (! Schema::hasTable('knowledge_embeddings')) {
            return false;
        }
        if (! $this->extensionInstallable()) {
            $this->forgetCache();

            return false;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        } catch (\Throwable $exception) {
            Log::warning('AI pgvector extension is not available on this PostgreSQL server', [
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
            $this->forgetCache();

            return false;
        }

        $this->forgetCache();
        if (! $this->extensionAvailable()) {
            return false;
        }

        try {
            if (! Schema::hasColumn('knowledge_embeddings', 'embedding_vector')) {
                DB::statement('ALTER TABLE knowledge_embeddings ADD COLUMN embedding_vector vector');
            }
        } catch (\Throwable $exception) {
            Log::warning('AI pgvector column could not be created', [
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
            $this->forgetCache();

            return false;
        }

        $this->forgetCache();
        $this->backfillNativeColumn();

        $savepoint = false;
        try {
            DB::unprepared('SAVEPOINT ai13_hnsw');
            $savepoint = true;
        } catch (\Throwable) {
            $savepoint = false;
        }
        try {
            DB::statement(PgvectorAnnQuery::hnswDdl());
            if ($savepoint) {
                DB::unprepared('RELEASE SAVEPOINT ai13_hnsw');
            }
        } catch (\Throwable $exception) {
            if ($savepoint) {
                try {
                    DB::unprepared('ROLLBACK TO SAVEPOINT ai13_hnsw');
                } catch (\Throwable) {
                }
            }
            Log::warning('AI pgvector HNSW index could not be created; native ORDER BY still used when the column exists', [
                'message' => AiErrorSanitizer::logMessage($exception),
            ]);
        }

        $this->forgetCache();

        return $this->nativeColumnAvailable();
    }

    public function rollback(): void
    {
        if (! $this->isPgsql() || ! Schema::hasTable('knowledge_embeddings')) {
            return;
        }
        try {
            DB::statement('DROP INDEX IF EXISTS '.PgvectorAnnQuery::INDEX_NAME);
        } catch (\Throwable) {
        }
        try {
            if (Schema::hasColumn('knowledge_embeddings', 'embedding_vector')) {
                DB::statement('ALTER TABLE knowledge_embeddings DROP COLUMN embedding_vector');
            }
        } catch (\Throwable) {
        }
        $this->forgetCache();
    }

    public function writeNativeVector(int $id, array $vector): void
    {
        if (! $this->nativeColumnAvailable()) {
            return;
        }
        $literal = PgvectorLiteral::format($vector);
        DB::statement(
            'UPDATE knowledge_embeddings SET embedding_vector = CAST(? AS vector) WHERE id = ?',
            [$literal, $id],
        );
    }

    public function forgetCache(): void
    {
        $this->extensionAvailable = null;
        $this->nativeColumnAvailable = null;
        $this->hnswAvailable = null;
    }

    private function backfillNativeColumn(): void
    {
        $rows = DB::table('knowledge_embeddings')
            ->whereNull('embedding_vector')
            ->orderBy('id')
            ->limit(5000)
            ->get(['id', 'embedding']);
        foreach ($rows as $row) {
            $vector = is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding;
            if (! is_array($vector) || $vector === []) {
                continue;
            }
            try {
                $this->writeNativeVector((int) $row->id, array_map(static fn ($value): float => (float) $value, array_values($vector)));
            } catch (\Throwable) {
                continue;
            }
        }
    }

    private function extensionInstallable(): bool
    {
        try {
            $row = DB::selectOne("SELECT 1 AS present FROM pg_available_extensions WHERE name = 'vector'");

            return $row !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function isPgsql(): bool
    {
        try {
            return Schema::getConnection()->getDriverName() === 'pgsql';
        } catch (\Throwable) {
            return false;
        }
    }
}
