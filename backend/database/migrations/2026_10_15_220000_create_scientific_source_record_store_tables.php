<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Durable record store of harvested and bulk-loaded scientific sources (ADR-023 §8.41).
 *
 * scientific_source_records is the authoritative store: one row per (source_key, provider
 * identifier) holding the retained record, its provenance and its lifecycle state; tombstones
 * stay as rows. scientific_source_states holds the committed generation and harvest checkpoint
 * of each source and is the row a commit locks. scientific_source_generations is the
 * append-only log of committed generations. The search index is derived from these rows and
 * is never stored here.
 *
 * Global reference data of public sources: no tenant or user data, no foreign keys to domain
 * tables. On PostgreSQL the lifecycle invariants are also enforced by CHECK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_source_states', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key', 64)->unique();
            $table->unsignedBigInteger('generation')->default(0);
            $table->json('checkpoint')->nullable();
            $table->string('last_mode', 16)->nullable();
            $table->string('last_committed_at', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('scientific_source_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key', 64);
            $table->unsignedBigInteger('generation');
            $table->string('mode', 16);
            $table->json('counts');
            $table->string('checkpoint_state', 16)->nullable();
            $table->string('committed_at', 40);
            $table->timestamps();

            $table->unique(['source_key', 'generation'], 'scientific_source_generations_source_generation_uq');
        });

        Schema::create('scientific_source_records', function (Blueprint $table): void {
            $table->id();
            $table->string('source_key', 64);
            $table->string('original_identifier', 512);
            $table->string('lifecycle_state', 16);
            $table->string('content_hash', 64)->nullable();
            $table->string('datestamp', 40)->nullable();
            $table->unsignedBigInteger('generation');
            $table->unsignedBigInteger('first_generation');
            $table->json('record')->nullable();
            $table->json('provenance');
            $table->string('tombstone_reason', 128)->nullable();
            $table->string('changed_at', 40);
            $table->timestamps();

            $table->unique(['source_key', 'original_identifier'], 'scientific_source_records_identity_uq');
            $table->index(['source_key', 'lifecycle_state'], 'scientific_source_records_source_state_idx');
        });

        if (DB::connection($this->getConnection())->getDriverName() === 'pgsql') {
            DB::connection($this->getConnection())->statement(
                "ALTER TABLE scientific_source_records ADD CONSTRAINT scientific_source_records_lifecycle_ck CHECK ("
                ."(lifecycle_state = 'active' AND record IS NOT NULL AND content_hash IS NOT NULL AND tombstone_reason IS NULL) OR "
                ."(lifecycle_state IN ('deleted', 'withdrawn') AND record IS NULL AND content_hash IS NULL AND tombstone_reason IS NOT NULL))"
            );
            DB::connection($this->getConnection())->statement(
                'ALTER TABLE scientific_source_records ADD CONSTRAINT scientific_source_records_generation_ck CHECK (first_generation >= 1 AND generation >= first_generation)'
            );
            DB::connection($this->getConnection())->statement(
                "ALTER TABLE scientific_source_generations ADD CONSTRAINT scientific_source_generations_mode_ck CHECK (generation >= 1 AND mode IN ('incremental', 'snapshot'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_source_records');
        Schema::dropIfExists('scientific_source_generations');
        Schema::dropIfExists('scientific_source_states');
    }
};
