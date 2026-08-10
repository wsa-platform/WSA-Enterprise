<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('tokens_used');
            $table->index(['organization_id', 'status', 'created_at']);
        });

        Schema::create('usage_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 64);
            $table->unsignedInteger('quantity')->default(1);
            $table->date('period_start');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'metric', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_records');

        Schema::table('ai_requests', function (Blueprint $table): void {
            $table->dropIndex(['organization_id', 'status', 'created_at']);
            $table->dropColumn('cancelled_at');
        });
    }
};
