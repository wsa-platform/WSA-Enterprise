<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * IC Persistence integrity (ADR-023 D1/D6): at most one ACTIVE record per adr_id.
 *
 * Read-only preflight first: if historical data already holds several ACTIVE rows for an
 * adr_id the migration fails, names those adr_id values, and changes nothing. Repair is a
 * reviewed manual decision; this migration never picks a winner or rewrites lifecycle_state.
 */
return new class extends Migration
{
    private const TABLE = 'cghia_integration_classification_records';

    private const INDEX = 'cghia_ic_records_single_active_uq';

    private const MAX_LISTED_ADR_IDS = 50;

    public function up(): void
    {
        $connection = DB::connection($this->getConnection());

        $driver = $connection->getDriverName();
        if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException(
                "IC single-ACTIVE index requires a partial unique index (pgsql or sqlite); driver [{$driver}] is not supported."
            );
        }

        $duplicates = $connection->table(self::TABLE)
            ->select('adr_id')
            ->selectRaw('COUNT(*) AS active_count')
            ->where('lifecycle_state', 'ACTIVE')
            ->groupBy('adr_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('adr_id')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $listed = $duplicates->take(self::MAX_LISTED_ADR_IDS)
                ->map(fn (object $row): string => $row->adr_id.' ('.(int) $row->active_count.' ACTIVE)')
                ->implode(', ');
            $more = $duplicates->count() > self::MAX_LISTED_ADR_IDS
                ? ' and '.($duplicates->count() - self::MAX_LISTED_ADR_IDS).' more'
                : '';

            throw new RuntimeException(
                'IC single-ACTIVE preflight failed: '.$duplicates->count().' adr_id value(s) hold more than one ACTIVE record: '
                .$listed.$more.'. No data was changed. Resolve each adr_id by reviewed manual repair, then re-run the migration.'
            );
        }

        $connection->statement(
            'CREATE UNIQUE INDEX '.self::INDEX.' ON '.self::TABLE." (adr_id) WHERE lifecycle_state = 'ACTIVE'"
        );
    }

    public function down(): void
    {
        DB::connection($this->getConnection())->statement('DROP INDEX IF EXISTS '.self::INDEX);
    }
};
