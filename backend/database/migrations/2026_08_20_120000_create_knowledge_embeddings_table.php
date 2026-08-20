<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_embeddings', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->json('embedding');
            $table->string('embedding_model', 128);
            $table->unsignedSmallInteger('embedding_dimensions');
            $table->string('content_hash', 64);
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['organization_id', 'source_type']);
            $table->index(['embedding_model', 'embedding_dimensions']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_embeddings');
    }
};
