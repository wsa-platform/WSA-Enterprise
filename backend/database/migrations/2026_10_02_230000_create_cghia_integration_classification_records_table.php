<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IC Persistence — Integration Classification decision store only.
 *
 * persistence_record_id encoding: bigint `$table->id()` (Laravel/Postgres project convention).
 * Storage identity only — not Membership SoT, Cap, Path, Projection, D-10, Stage-3, or B7.
 * No domain FKs (matches FS-01-ID / IU-07 durable_correlation_records pattern).
 *
 * DESIGN AUTHORITY: ADR-023 §8.14. IMPLEMENTATION AUTHORIZATION: IC Persistence unit only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cghia_integration_classification_records', function (Blueprint $table): void {
            $table->id(); // persistence_record_id (storage bigint encoding)

            $table->string('adr_id', 255);
            $table->string('canonical_identity_id', 255)->nullable();
            $table->string('identity_binding_ref', 255)->nullable();
            $table->string('classification_decision_identity', 255);
            $table->string('classification_status', 32);

            $table->json('access_modality_claims');
            $table->string('integration_nature', 64);
            $table->string('integration_boundary', 64);
            $table->string('protocol_family', 128)->nullable();
            $table->boolean('source_specific_requirement');
            $table->text('source_specific_rationale')->nullable();
            $table->string('path_family_hint', 32)->nullable(); // NON-AUTHORITATIVE
            $table->string('existing_adapter_reference', 512)->nullable();
            $table->string('external_dependency_reference', 512)->nullable();

            $table->json('evidence_references')->nullable();
            $table->string('evidence_fingerprint', 255);

            $table->string('license_reference', 512)->nullable();
            $table->string('access_reference', 512)->nullable();
            $table->string('reuse_reference', 512)->nullable();
            $table->text('rationale')->nullable();

            $table->string('decision_actor', 255);
            $table->string('verified_at', 64);
            $table->string('decision_timestamp', 64);

            $table->string('lifecycle_state', 32);
            $table->unsignedInteger('schema_version');
            $table->string('idempotency_key', 191)->unique();
            $table->unsignedBigInteger('superseded_by')->nullable();

            // Non-authoritative diagnostic metadata only (never Cap/Path/D-10/C9/CSQ authority)
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('lifecycle_state');
            $table->index('adr_id');
            $table->index('classification_decision_identity');
            $table->index(['adr_id', 'lifecycle_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cghia_integration_classification_records');
    }
};
