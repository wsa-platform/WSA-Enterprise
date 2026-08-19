<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bee_knowledge_topics', function (Blueprint $table): void {
            $table->longText('body')->nullable();
        });

        Schema::table('ai_usage_records', function (Blueprint $table): void {
            $table->json('retrieval')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bee_knowledge_topics', function (Blueprint $table): void {
            $table->dropColumn('body');
        });

        Schema::table('ai_usage_records', function (Blueprint $table): void {
            $table->dropColumn('retrieval');
        });
    }
};
