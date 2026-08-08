<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_types', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->string('code',32); $table->string('name'); $table->string('scientific_name')->nullable(); $table->text('description')->nullable(); $table->timestamps(); $table->unique(['organization_id','code']);
        });
        Schema::create('crop_varieties', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('crop_type_id')->constrained()->cascadeOnDelete(); $table->string('code',32); $table->string('name'); $table->string('supplier')->nullable(); $table->unsignedInteger('maturity_days')->nullable(); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['crop_type_id','code']);
        });
        Schema::create('crop_seasons', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('code',32); $table->date('starts_at'); $table->date('ends_at')->nullable(); $table->string('status')->default('planned'); $table->timestamps(); $table->unique(['organization_id','code']);
        });
        Schema::create('growth_stages', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('crop_type_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->unsignedInteger('sequence')->default(0); $table->unsignedInteger('expected_days')->nullable(); $table->text('description')->nullable(); $table->timestamps();
        });
        Schema::create('crop_harvests', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('season_id')->nullable()->constrained('crop_seasons')->nullOnDelete(); $table->foreignId('crop_type_id')->constrained()->restrictOnDelete(); $table->foreignId('variety_id')->nullable()->constrained('crop_varieties')->nullOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete(); $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete(); $table->date('harvested_at'); $table->decimal('quantity',14,3); $table->string('unit',16)->default('kg'); $table->decimal('quality_score',5,2)->nullable(); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('crop_yields', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('season_id')->nullable()->constrained('crop_seasons')->nullOnDelete(); $table->foreignId('crop_type_id')->constrained()->restrictOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete(); $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete(); $table->decimal('area_hectares',12,3)->default(0); $table->decimal('expected_quantity',14,3)->nullable(); $table->decimal('actual_quantity',14,3)->default(0); $table->string('unit',16)->default('kg'); $table->date('reported_at'); $table->text('notes')->nullable(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('crop_yields'); Schema::dropIfExists('crop_harvests'); Schema::dropIfExists('growth_stages'); Schema::dropIfExists('crop_seasons'); Schema::dropIfExists('crop_varieties'); Schema::dropIfExists('crop_types'); }
};
