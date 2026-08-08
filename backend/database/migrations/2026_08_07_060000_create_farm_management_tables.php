<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32); $table->string('name'); $table->string('owner_name')->nullable(); $table->text('address')->nullable(); $table->decimal('area_hectares', 12, 3)->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['organization_id', 'code']);
        });
        Schema::create('farm_regions', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32); $table->string('name'); $table->text('description')->nullable(); $table->decimal('area_hectares', 12, 3)->default(0); $table->timestamps(); $table->unique(['farm_id', 'code']);
        });
        Schema::create('farm_fields', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->constrained()->cascadeOnDelete(); $table->foreignId('region_id')->nullable()->constrained('farm_regions')->nullOnDelete();
            $table->string('code', 32); $table->string('name'); $table->decimal('area_hectares', 12, 3)->default(0); $table->string('soil_type')->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->unique(['farm_id', 'code']);
        });
        Schema::create('farm_blocks', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('field_id')->constrained('farm_fields')->cascadeOnDelete();
            $table->string('code', 32); $table->string('name'); $table->decimal('area_hectares', 12, 3)->default(0); $table->string('crop')->nullable(); $table->string('variety')->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->unique(['field_id', 'code']);
        });
        Schema::create('greenhouses', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->constrained()->cascadeOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete();
            $table->string('code', 32); $table->string('name'); $table->decimal('area_square_meters', 12, 2)->default(0); $table->string('structure_type')->nullable(); $table->string('climate_control')->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->unique(['farm_id', 'code']);
        });
        Schema::create('irrigation_zones', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->constrained()->cascadeOnDelete(); $table->foreignId('field_id')->nullable()->constrained('farm_fields')->nullOnDelete(); $table->foreignId('block_id')->nullable()->constrained('farm_blocks')->nullOnDelete(); $table->foreignId('greenhouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 32); $table->string('name'); $table->string('method')->nullable(); $table->decimal('flow_rate_lph', 12, 2)->nullable(); $table->string('status')->default('active'); $table->timestamps(); $table->unique(['farm_id', 'code']);
        });
        Schema::create('gps_coordinates', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->string('coordinateable_type'); $table->unsignedBigInteger('coordinateable_id'); $table->decimal('latitude', 10, 7); $table->decimal('longitude', 10, 7); $table->decimal('altitude_meters', 10, 2)->nullable(); $table->unsignedInteger('sequence')->default(0); $table->timestamps(); $table->index(['coordinateable_type', 'coordinateable_id']);
        });
        Schema::create('gis_maps', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('farm_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('layer_type', 64); $table->string('source_url')->nullable(); $table->json('geojson')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gis_maps'); Schema::dropIfExists('gps_coordinates'); Schema::dropIfExists('irrigation_zones'); Schema::dropIfExists('greenhouses'); Schema::dropIfExists('farm_blocks'); Schema::dropIfExists('farm_fields'); Schema::dropIfExists('farm_regions'); Schema::dropIfExists('farms');
    }
};
