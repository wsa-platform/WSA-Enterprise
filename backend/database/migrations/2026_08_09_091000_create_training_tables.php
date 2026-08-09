<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('locale', 8)->default('ar');
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('training_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_ar')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 32)->default('draft');
            $table->timestamps();
            $table->unique(['course_id', 'code']);
        });

        Schema::create('training_objectives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('training_lessons')->cascadeOnDelete();
            $table->string('objective');
            $table->string('objective_ar')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_quizzes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('training_lessons')->cascadeOnDelete();
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->unsignedInteger('passing_score')->default(70);
            $table->timestamps();
        });

        Schema::create('training_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('training_quizzes')->cascadeOnDelete();
            $table->string('question');
            $table->string('question_ar')->nullable();
            $table->string('question_type', 32)->default('multiple_choice');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('training_courses')->cascadeOnDelete();
            $table->string('status', 32)->default('active');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('training_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('training_enrollments')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('training_lessons')->cascadeOnDelete();
            $table->string('status', 32)->default('not_started');
            $table->unsignedInteger('score')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'lesson_id']);
        });

        Schema::create('training_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('training_enrollments')->cascadeOnDelete();
            $table->string('certificate_code', 64);
            $table->timestamp('issued_at')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'certificate_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_certificates');
        Schema::dropIfExists('training_progress');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_questions');
        Schema::dropIfExists('training_quizzes');
        Schema::dropIfExists('training_objectives');
        Schema::dropIfExists('training_lessons');
        Schema::dropIfExists('training_courses');
    }
};
