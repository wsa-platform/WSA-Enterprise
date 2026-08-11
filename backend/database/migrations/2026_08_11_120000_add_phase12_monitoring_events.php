<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component', 64);
            $table->string('status', 32);
            $table->string('severity', 16)->default('warning');
            $table->string('lifecycle_stage', 32)->default('detected');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('details')->nullable();
            $table->string('remediation_status', 32)->nullable();
            $table->string('remediation_action', 64)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->text('analysis_summary')->nullable();
            $table->timestamps();

            $table->index(['component', 'status', 'detected_at']);
            $table->index(['lifecycle_stage', 'severity']);
            $table->index('correlation_id');
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_events');
    }
};
