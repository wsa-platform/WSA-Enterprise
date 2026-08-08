<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soil_analyses', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete(); $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete();
            $table->string('sample_reference',64); $table->date('sampled_at'); $table->decimal('ph',4,2)->nullable(); $table->decimal('ec',10,3)->nullable(); $table->decimal('organic_matter_percent',6,3)->nullable(); $table->decimal('moisture_percent',6,3)->nullable(); $table->string('laboratory')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['organization_id','sample_reference']);
        });
        Schema::create('soil_nutrients', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('soil_analysis_id')->constrained()->cascadeOnDelete(); $table->string('nutrient',32); $table->decimal('value',14,4); $table->string('unit',32); $table->decimal('target_min',14,4)->nullable(); $table->decimal('target_max',14,4)->nullable(); $table->string('status',32)->nullable(); $table->timestamps(); $table->unique(['soil_analysis_id','nutrient']);
        });
        Schema::create('soil_recommendations', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('soil_analysis_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete(); $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete();
            $table->string('title'); $table->text('recommendation'); $table->string('category',64)->nullable(); $table->string('priority',32)->default('medium'); $table->string('status',32)->default('open'); $table->date('due_at')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('soil_recommendations'); Schema::dropIfExists('soil_nutrients'); Schema::dropIfExists('soil_analyses'); }
};
