<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnosis_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('diagnosis_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('diagnosis_categories')->nullOnDelete();
            $table->foreignId('crop_type_id')->nullable()->constrained('crop_types')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('subject_type', 32)->default('crop');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('diagnosis_symptoms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('diagnosis_subjects')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('diagnosis_diseases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('diagnosis_subjects')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('scientific_name')->nullable();
            $table->text('description')->nullable();
            $table->string('default_severity', 32)->default('medium');
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('diagnosis_disease_symptom', function (Blueprint $table): void {
            $table->foreignId('disease_id')->constrained('diagnosis_diseases')->cascadeOnDelete();
            $table->foreignId('symptom_id')->constrained('diagnosis_symptoms')->cascadeOnDelete();
            $table->primary(['disease_id', 'symptom_id']);
        });

        Schema::create('diagnosis_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete();
            $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete();
            $table->foreignId('crop_type_id')->nullable()->constrained('crop_types')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('diagnosis_subjects')->nullOnDelete();
            $table->string('reference', 64);
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->string('image_disk', 32)->nullable();
            $table->string('image_path')->nullable();
            $table->json('symptom_ids')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference']);
        });

        Schema::create('diagnosis_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('disease_id')->nullable()->constrained('diagnosis_diseases')->nullOnDelete();
            $table->string('title');
            $table->text('summary');
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->string('severity', 32)->default('medium');
            $table->string('priority', 32)->default('medium');
            $table->string('provider', 32)->default('mock');
            $table->boolean('is_decision_support')->default(true);
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnosis_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnosis_result_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('recommendation');
            $table->string('category', 64)->nullable();
            $table->string('priority', 32)->default('medium');
            $table->string('status', 32)->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_recommendations');
        Schema::dropIfExists('diagnosis_results');
        Schema::dropIfExists('diagnosis_requests');
        Schema::dropIfExists('diagnosis_disease_symptom');
        Schema::dropIfExists('diagnosis_diseases');
        Schema::dropIfExists('diagnosis_symptoms');
        Schema::dropIfExists('diagnosis_subjects');
        Schema::dropIfExists('diagnosis_categories');
    }
};
