<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('library_categories')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('library_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 64);
            $table->string('name_ar', 64)->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('library_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('library_categories')->nullOnDelete();
            $table->foreignId('crop_type_id')->nullable()->constrained('crop_types')->nullOnDelete();
            $table->string('slug', 128);
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('summary')->nullable();
            $table->text('summary_ar')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_ar')->nullable();
            $table->string('item_type', 32)->default('article');
            $table->string('author')->nullable();
            $table->string('source')->nullable();
            $table->string('locale', 8)->default('ar');
            $table->string('publication_status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('file_disk', 32)->nullable();
            $table->string('file_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('library_item_tag', function (Blueprint $table): void {
            $table->foreignId('library_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('library_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['library_item_id', 'library_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_item_tag');
        Schema::dropIfExists('library_items');
        Schema::dropIfExists('library_tags');
        Schema::dropIfExists('library_categories');
    }
};
