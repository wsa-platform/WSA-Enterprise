<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('slug');
            $table->index('is_active');
        });

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
        });

        Schema::create('media_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('storage_disk', 32)->default('public');
            $table->string('storage_path');
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('context', 64)->default('general');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'context']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_upload_id')->nullable()->constrained('media_uploads')->nullOnDelete();
            $table->string('storage_disk', 32)->default('public');
            $table->string('storage_path');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('media_uploads');

        Schema::table('inventory_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
