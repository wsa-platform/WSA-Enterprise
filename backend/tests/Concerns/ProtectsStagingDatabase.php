<?php

namespace Tests\Concerns;

use Tests\Support\DatabaseSafety;

trait ProtectsStagingDatabase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSafety::assertTestingDatabaseIsIsolated();
    }
}
