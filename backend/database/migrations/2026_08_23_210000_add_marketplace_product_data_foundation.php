<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_units', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_attribute_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug');
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('data_type', 32)->default('string');
            $table->foreignId('category_id')->nullable()->constrained('marketplace_categories')->nullOnDelete();
            $table->string('product_type', 64)->nullable()->index();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique('slug');
        });

        Schema::table('marketplace_listings', function (Blueprint $table): void {
            $table->string('origin_country', 2)->nullable()->index()->after('country');
            $table->string('seller_region')->nullable()->after('city');
            $table->string('product_type', 64)->nullable()->index()->after('category_id');
            $table->string('brand')->nullable()->after('title');
            $table->string('availability', 32)->nullable()->index()->after('status');
            $table->foreignId('unit_id')->nullable()->after('availability')->constrained('marketplace_units')->nullOnDelete();
            $table->decimal('min_order_quantity', 14, 3)->nullable();
            $table->decimal('available_quantity', 14, 3)->nullable();
            $table->decimal('production_capacity', 14, 3)->nullable();
            $table->boolean('wholesale')->default(false);
            $table->boolean('retail')->default(false);
            $table->text('packaging')->nullable();
            $table->text('shipping_terms')->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->json('specifications')->nullable();
            $table->string('video_url', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_listings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn([
                'origin_country',
                'seller_region',
                'product_type',
                'brand',
                'availability',
                'min_order_quantity',
                'available_quantity',
                'production_capacity',
                'wholesale',
                'retail',
                'packaging',
                'shipping_terms',
                'lead_time_days',
                'specifications',
                'video_url',
            ]);
        });

        Schema::dropIfExists('marketplace_attribute_definitions');
        Schema::dropIfExists('marketplace_units');
    }
};
