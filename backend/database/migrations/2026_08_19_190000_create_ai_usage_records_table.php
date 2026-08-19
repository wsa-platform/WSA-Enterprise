<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->string('provider', 32);
            $table->string('model', 128)->nullable();
            $table->string('provider_request_id', 128)->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status', 32);
            $table->string('error_category', 64)->nullable();
            $table->timestamps();

            $table->unique('ai_request_id');
            $table->index(['organization_id', 'created_at']);
            $table->index(['organization_id', 'user_id']);
            $table->index(['organization_id', 'provider', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_records');
    }
};
