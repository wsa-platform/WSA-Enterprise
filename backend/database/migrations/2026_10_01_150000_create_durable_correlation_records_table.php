<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IU-07 Durable Correlation store only.
 *
 * persistence_record_id encoding: bigint `$table->id()` (Laravel/Postgres project convention).
 * Storage identity only — not adr_id / path_id / projection_identity / Cap / CSQ identity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('durable_correlation_records', function (Blueprint $table): void {
            $table->id(); // persistence_record_id (storage bigint encoding)

            // B7 opaque reference columns (no domain FKs — v1)
            $table->string('question_identity', 255);
            $table->string('csq_identity', 255);
            $table->string('variant_id', 255)->nullable();
            $table->string('adr_id', 255);
            $table->string('canonical_identity_id', 255)->nullable();
            $table->string('capability_decision_identity', 255);
            $table->string('path_id', 255);
            $table->string('path_decision_identity', 255);
            $table->string('projection_identity', 255);
            $table->string('retrieval_timestamp', 64)->nullable();
            $table->string('original_source_identifier', 512)->nullable();
            $table->string('aggregator_record_identifier', 512)->nullable();

            $table->boolean('composite_is_canonical_source_identity')->default(false);

            $table->string('lifecycle_state', 32);
            $table->unsignedInteger('schema_version');
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedBigInteger('superseded_by')->nullable();

            // Non-authoritative diagnostic metadata only (never C9/R6/CSQ authority)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('lifecycle_state');
            $table->index('adr_id');
            $table->index('projection_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('durable_correlation_records');
    }
};
