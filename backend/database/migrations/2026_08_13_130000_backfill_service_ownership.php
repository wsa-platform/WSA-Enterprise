<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diagnosis_results') && Schema::hasColumn('diagnosis_results', 'owner_user_id')) {
            DB::statement('
                UPDATE diagnosis_results
                SET owner_user_id = (
                    SELECT dr.owner_user_id
                    FROM diagnosis_requests dr
                    WHERE dr.id = diagnosis_results.diagnosis_request_id
                )
                WHERE owner_user_id IS NULL
                  AND diagnosis_request_id IS NOT NULL
                  AND EXISTS (
                    SELECT 1 FROM diagnosis_requests dr
                    WHERE dr.id = diagnosis_results.diagnosis_request_id
                      AND dr.owner_user_id IS NOT NULL
                  )
            ');
        }

        if (Schema::hasTable('diagnosis_recommendations') && Schema::hasColumn('diagnosis_recommendations', 'owner_user_id')) {
            DB::statement('
                UPDATE diagnosis_recommendations
                SET owner_user_id = (
                    SELECT dr.owner_user_id
                    FROM diagnosis_results dres
                    INNER JOIN diagnosis_requests dr ON dr.id = dres.diagnosis_request_id
                    WHERE dres.id = diagnosis_recommendations.diagnosis_result_id
                )
                WHERE owner_user_id IS NULL
                  AND diagnosis_result_id IS NOT NULL
                  AND EXISTS (
                    SELECT 1
                    FROM diagnosis_results dres
                    INNER JOIN diagnosis_requests dr ON dr.id = dres.diagnosis_request_id
                    WHERE dres.id = diagnosis_recommendations.diagnosis_result_id
                      AND dr.owner_user_id IS NOT NULL
                  )
            ');
        }

        foreach (config('service_ownership.backfill_from_user_id_tables', []) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'owner_user_id') || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('owner_user_id')
                ->whereNotNull('user_id')
                ->update(['owner_user_id' => DB::raw('user_id')]);
        }

        foreach (config('service_ownership.backfill_from_created_by_user_id_tables', []) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'owner_user_id') || ! Schema::hasColumn($table, 'created_by_user_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('owner_user_id')
                ->whereNotNull('created_by_user_id')
                ->update(['owner_user_id' => DB::raw('created_by_user_id')]);
        }
    }

    public function down(): void
    {
        // Backfill is not reversed automatically; NULL owners remain supervisor-only.
    }
};
