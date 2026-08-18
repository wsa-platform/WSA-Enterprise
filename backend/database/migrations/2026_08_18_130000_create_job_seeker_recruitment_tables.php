<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_seeker_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('specialization')->nullable()->index();
            $table->text('biography')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('certifications')->nullable();
            $table->json('languages')->nullable();
            $table->string('cv_path')->nullable();
            $table->decimal('desired_salary', 12, 2)->nullable();
            $table->string('salary_currency', 3)->default('SAR');
            $table->date('availability_date')->nullable();
            $table->string('recruitment_status')->default('new')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employment_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_seeker_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['job_seeker_profile_id', 'created_at']);
        });

        Schema::create('recruiter_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_seeker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_private')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['job_seeker_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruiter_notes');
        Schema::dropIfExists('employment_status_history');
        Schema::dropIfExists('job_seeker_profiles');
    }
};
