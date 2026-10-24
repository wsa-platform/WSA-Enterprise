<?php

declare(strict_types=1);

namespace Tests\Unit\Agriculture\Research\Capability\CapVer;

use App\Services\Agriculture\Research\Capability\CapVer\CapVerSeatVerificationService;
use App\Services\Agriculture\Research\Capability\Persistence\EloquentCapabilityStoreRepository;
use App\Services\Agriculture\Research\Onboarding\AcceptedOwnerDecisionCatalog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\Support\DatabaseSafety;
use Tests\TestCase;

/**
 * The authorized CapVer contract is the five-seat catalog, one current cell each.
 * The database is a disposable file under storage/framework/testing.
 */
final class CapVerAuthorizedCellCountTest extends TestCase
{
    private const EXPECTED_SEATS = ['G1-02', 'G6-20', 'G1-01', 'G1-17', 'G5-04'];

    private string $database;

    private int $scientificLength;

    private int $scientificMtime;

    private int $capverLength;

    private int $capverMtime;

    protected function setUp(): void
    {
        parent::setUp();

        $scientific = DatabaseSafety::operationalScientificDatabasePath();
        $operationalCapVer = storage_path('app'.DIRECTORY_SEPARATOR.'capver.sqlite');
        $this->scientificLength = filesize($scientific);
        $this->scientificMtime = filemtime($scientific);
        $this->capverLength = filesize($operationalCapVer);
        $this->capverMtime = filemtime($operationalCapVer);

        $this->database = storage_path(
            'framework'.DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'capver_authorized_cell_count_'.uniqid('', true).'.sqlite'
        );
        $this->deleteOwned($this->database);

        config([
            'capability_store.connection' => 'capver',
            'database.connections.capver.driver' => 'sqlite',
            'database.connections.capver.url' => '',
            'database.connections.capver.database' => $this->database,
        ]);
        DB::purge('capver');
        DB::disconnect('capver');

        DatabaseSafety::assertCapVerStoreIsIsolated();
        $resolved = (string) config('database.connections.capver.database');
        $this->assertSame($this->database, $resolved);
        $this->assertStringContainsString('framework'.DIRECTORY_SEPARATOR.'testing', $resolved);
        $this->assertNotSame($operationalCapVer, $resolved);
        $this->assertNotSame($scientific, $resolved);
        $this->assertSame(':memory:', (string) config('database.connections.'.config('database.default').'.database'));

        $this->assertSame(0, Artisan::call('migrate', [
            '--database' => 'capver',
            '--path' => 'database/migrations/2026_10_09_140000_create_cghia_capability_cell_records_table.php',
            '--force' => true,
        ]));

        $open = (string) DB::connection('capver')->getDatabaseName();
        $this->assertSame($this->database, $open);
        $this->assertNotSame($operationalCapVer, $open);
    }

    protected function tearDown(): void
    {
        DB::disconnect('capver');
        $this->deleteOwned($this->database);
        $this->assertSame($this->scientificLength, filesize(DatabaseSafety::operationalScientificDatabasePath()));
        $this->assertSame($this->scientificMtime, filemtime(DatabaseSafety::operationalScientificDatabasePath()));
        $this->assertSame($this->capverLength, filesize(storage_path('app'.DIRECTORY_SEPARATOR.'capver.sqlite')));
        $this->assertSame($this->capverMtime, filemtime(storage_path('app'.DIRECTORY_SEPARATOR.'capver.sqlite')));
        parent::tearDown();
    }

    public function test_authorized_contract_persists_exactly_five_current_cells(): void
    {
        $this->assertSame(self::EXPECTED_SEATS, AcceptedOwnerDecisionCatalog::capVerAuthorizedSeats());

        $service = new CapVerSeatVerificationService(new EloquentCapabilityStoreRepository);
        $created = $service->executeAcceptedSeats();
        $this->assertCount(5, $created);
        foreach ($created as $index => $outcome) {
            $this->assertSame(self::EXPECTED_SEATS[$index], $outcome->adrId);
            $this->assertSame('PERSISTED', $outcome->capabilityStoreStatus, $outcome->adrId);
        }

        $rows = $this->currentRows();
        $this->assertCount(5, $rows);
        $this->assertSame(self::EXPECTED_SEATS, array_column($rows, 'adr_id'));
        $this->assertSame(
            [
                'cap-rec-r11-20261018-G1-02-oai_pmh',
                'cap-rec-r11-20261018-G6-20-api',
                'cap-rec-r12-20261018-G1-01-bulk',
                'cap-rec-r12-20261018-G1-17-static',
                'cap-rec-r9-20261016-G5-04-api',
            ],
            array_column($rows, 'capability_record_id')
        );

        $repeated = $service->executeAcceptedSeats();
        foreach ($repeated as $outcome) {
            $this->assertSame('ALREADY_CURRENT', $outcome->capabilityStoreStatus, $outcome->adrId);
        }
        $this->assertCount(5, $this->currentRows());
        $this->assertFalse(
            DB::connection((string) config('database.default'))->getSchemaBuilder()->hasTable('cghia_capability_cell_records')
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currentRows(): array
    {
        $pdo = new PDO('sqlite:'.$this->database, null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $statement = $pdo->query(
            "SELECT adr_id, capability_record_id FROM cghia_capability_cell_records WHERE cell_lifecycle = 'CURRENT' ORDER BY id"
        );

        return $statement->fetchAll();
    }

    private function deleteOwned(string $path): void
    {
        $normalized = str_replace('\\', '/', $path);
        if (! str_contains($normalized, '/storage/framework/testing/capver_authorized_cell_count_')) {
            return;
        }
        foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
