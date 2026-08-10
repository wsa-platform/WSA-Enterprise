<?php

namespace Tests\Unit;

use Tests\Support\DatabaseSafety;
use Tests\TestCase;

class ProtectsStagingDatabaseTest extends TestCase
{
    public function test_refuses_staging_database_name(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => 'wsa_enterprise']);

        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->expectExceptionMessage('Refusing to run destructive tests against staging database');

        DatabaseSafety::assertTestingDatabaseIsIsolated();
    }

    public function test_allows_isolated_sqlite_memory_database(): void
    {
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);

        DatabaseSafety::assertTestingDatabaseIsIsolated();
        $this->assertTrue(true);
    }
}
