<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FS-01-ID — Identity Binding Persistence store only.
 *
 * persistence_record_id encoding: bigint `$table->id()` (Laravel/Postgres project convention).
 * Storage identity only — not Membership SoT, Cap, Path, Projection, D-10, or B7.
 * No domain FKs (matches IU-07 durable_correlation_records pattern).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cghia_identity_binding_records', function (Blueprint $table): void {
            $table->id(); // persistence_record_id (storage bigint encoding)

            $table->string('adr_id', 255);
            $table->string('canonical_identity_id', 255)->nullable();
            $table->string('identity_status', 32);
            $table->string('identity_decision_identity', 255);
            $table->string('identity_evidence_fingerprint', 255);
            $table->json('identity_evidence_refs')->nullable();
            $table->string('original_source_identifier', 512)->nullable();
            $table->string('aggregator_record_identifier', 512)->nullable();

            $table->string('lifecycle_state', 32);
            $table->unsignedInteger('schema_version');
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedBigInteger('superseded_by')->nullable();

            $table->string('decision_actor', 255);
            $table->string('decision_timestamp', 64);

            // Non-authoritative diagnostic metadata only (never Cap/Path/D-10/C9/CSQ authority)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('lifecycle_state');
            $table->index('adr_id');
            $table->index('identity_decision_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cghia_identity_binding_records');
    }
};
