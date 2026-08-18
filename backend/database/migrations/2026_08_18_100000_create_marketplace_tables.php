<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('marketplace_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('marketplace_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('seller_type', 32)->default('local')->index();
            $table->string('status', 32)->default('draft')->index();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->string('country')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('seller_display_name');
            $table->string('seller_email')->nullable();
            $table->string('seller_phone')->nullable();
            $table->boolean('seller_verified')->default(false);
            $table->boolean('export_ready')->default(false);
            $table->string('export_destination')->nullable();
            $table->json('export_metadata')->nullable();
            $table->decimal('contact_access_price', 12, 2)->default(0);
            $table->string('contact_access_currency', 3)->default('SAR');
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seller_user_id', 'status']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('marketplace_listing_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_listing_status_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('status')->index();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'created_at']);
        });

        Schema::create('contact_access_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('payment_status', 32)->default('pending')->index();
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['buyer_user_id', 'listing_id']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::create('marketplace_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('buyer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('contact_access_orders')->nullOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['buyer_user_id', 'listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_entitlements');
        Schema::dropIfExists('contact_access_orders');
        Schema::dropIfExists('marketplace_listing_status_history');
        Schema::dropIfExists('marketplace_listing_images');
        Schema::dropIfExists('marketplace_listings');
        Schema::dropIfExists('marketplace_categories');
    }
};
