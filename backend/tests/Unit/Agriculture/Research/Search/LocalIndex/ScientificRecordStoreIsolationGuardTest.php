<?php

namespace Tests\Unit\Agriculture\Research\Search\LocalIndex;

use PHPUnit\Framework\AssertionFailedError;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * Proves the scientific-store guard rejects unsafe configuration before any connection is opened.
 * These cases pass path strings only. They do not call the database.
 */
class ScientificRecordStoreIsolationGuardTest extends TestCase
{
    public function test_the_booted_scientific_connection_is_inside_the_testing_root(): void
    {
        $path = (string) config('database.connections.scientific_records.database');
        fwrite(STDERR, 'RESOLVED_SCIENTIFIC_DATABASE='.$path."\n");
        DatabaseSafety::assertScientificRecordStoreIsIsolated();
        $this->assertStringContainsString('scientific_records_', $path);
        $this->assertStringContainsString(
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, 'framework'.DIRECTORY_SEPARATOR.'testing'),
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path)
        );
    }

    public function test_the_operational_scientific_path_is_rejected(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('operational store');
        DatabaseSafety::assertScientificConnectionConfigurationIsIsolated([
            'driver' => 'sqlite',
            'url' => '',
            'database' => DatabaseSafety::operationalScientificDatabasePath(),
        ]);
    }

    public function test_a_parent_directory_segment_cannot_escape_the_testing_root(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('parent-directory segment');
        DatabaseSafety::assertScientificConnectionConfigurationIsIsolated([
            'driver' => 'sqlite',
            'url' => '',
            'database' => DatabaseSafety::permittedScientificTestingRoot().DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'scientific_record_store.sqlite',
        ]);
    }

    public function test_a_path_outside_the_testing_root_is_rejected(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('outside the testing root');
        DatabaseSafety::assertScientificConnectionConfigurationIsIsolated([
            'driver' => 'sqlite',
            'url' => '',
            'database' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'wsa-not-owned-scientific.sqlite',
        ]);
    }

    public function test_a_url_cannot_override_the_scientific_database_path(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('URL');
        DatabaseSafety::assertScientificConnectionConfigurationIsIsolated([
            'driver' => 'sqlite',
            'url' => 'sqlite:///tmp/not-the-test-store.sqlite',
            'database' => DatabaseSafety::permittedScientificTestingRoot().DIRECTORY_SEPARATOR.'harmless.sqlite',
        ]);
    }

    public function test_a_resolved_operational_path_is_rejected_without_opening_it(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('operational store');
        DatabaseSafety::assertScientificRecordStoreIsIsolated(DatabaseSafety::operationalScientificDatabasePath());
    }

    public function test_a_missing_scientific_database_path_is_rejected(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('no test-owned file path');
        DatabaseSafety::assertScientificConnectionConfigurationIsIsolated([
            'driver' => 'sqlite',
            'url' => '',
            'database' => '',
        ]);
    }
}
