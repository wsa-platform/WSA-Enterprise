<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * R7 — Positive-feedback-only research Feedback Dataset.
 * Negative feedback MUST NOT be persisted (enforced in application layer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_feedback_dataset', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('polarity', 16); // only 'positive' is written
            $table->string('research_source', 32)->default('home'); // home | crop
            $table->string('question_language', 8)->nullable();
            $table->string('answer_language', 8)->nullable();
            $table->string('ui_locale', 8)->nullable();
            $table->text('question')->nullable();
            $table->unsignedBigInteger('library_item_id')->nullable();
            $table->string('research_fingerprint', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'polarity']);
            $table->index(['research_source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_feedback_dataset');
    }
};
