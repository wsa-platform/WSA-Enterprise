<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexIngestionService;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalIndexSearchEngine;
use App\Services\Agriculture\Research\Search\LocalIndex\LocalScientificIndex;
use App\Services\Agriculture\Research\Search\LocalIndex\ScientificRecordStore;
use App\Services\Agriculture\Research\Search\LocalIndex\SqliteFtsScientificIndex;

/**
 * ADR-023 §8.41 PG suite: LocalIndex durable store must use the Guard-certified
 * pgsql_testing connection. CapVer/scientific_records SQLite isolation in
 * DatabaseSafety remains unchanged — only the LocalIndex binding is redirected.
 */
trait BindsPgsqlLocalIndexForGuardedSuite
{
    protected function bindLocalIndexToGuardedPgsqlTesting(): void
    {
        config(['agricultural_intelligence.local_index.connection' => 'pgsql_testing']);

        foreach ([
            ScientificRecordStore::class,
            LocalScientificIndex::class,
            SqliteFtsScientificIndex::class,
            LocalIndexSearchEngine::class,
            LocalIndexIngestionService::class,
        ] as $abstract) {
            if (app()->bound($abstract)) {
                app()->forgetInstance($abstract);
            }
        }
    }
}
