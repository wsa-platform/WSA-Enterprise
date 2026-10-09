<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cap Store Persistence — append-only Cap cell facts (ADR-023 §8.16 CPD-A–D).
 *
 * `id` is storage encoding only; capability_record_id is the fact identity.
 * No domain FKs (matches IC / Identity Binding / durable_correlation_records pattern).
 *
 * CPD-D: at most one CURRENT fact per (adr_id, canonical_identity_id, dimension_family,
 * dimension_code, seat_override_applied). canonical_identity_id is nullable, so two
 * partial unique indexes split the NULL / non-NULL cases (portable to PostgreSQL and SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cghia_capability_cell_records', function (Blueprint $table): void {
            $table->id();

            $table->string('capability_record_id', 255)->unique();

            $table->string('adr_id', 255);
            $table->string('canonical_identity_id', 255)->nullable();
            $table->string('dimension_family', 64);
            $table->string('dimension_code', 191);
            $table->boolean('seat_override_applied');

            $table->string('capability_state', 32);
            $table->string('evidence_freshness_class', 16);
            $table->string('snapshot_version', 255);
            $table->string('update_method', 255);
            $table->text('limitation_text')->nullable();
            $table->string('verified_at', 64)->nullable();
            $table->string('observed_at', 64)->nullable();

            $table->json('capability_evidence_refs');

            $table->string('cell_lifecycle', 16);

            $table->timestamps();

            $table->index(['adr_id', 'canonical_identity_id', 'cell_lifecycle'], 'cghia_cap_cells_subject_lifecycle_idx');
        });

        DB::statement(
            "CREATE UNIQUE INDEX cghia_cap_cells_current_canonical_uq
                ON cghia_capability_cell_records (adr_id, canonical_identity_id, dimension_family, dimension_code, seat_override_applied)
                WHERE cell_lifecycle = 'CURRENT' AND canonical_identity_id IS NOT NULL"
        );

        DB::statement(
            "CREATE UNIQUE INDEX cghia_cap_cells_current_seat_scoped_uq
                ON cghia_capability_cell_records (adr_id, dimension_family, dimension_code, seat_override_applied)
                WHERE cell_lifecycle = 'CURRENT' AND canonical_identity_id IS NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cghia_capability_cell_records');
    }
};
