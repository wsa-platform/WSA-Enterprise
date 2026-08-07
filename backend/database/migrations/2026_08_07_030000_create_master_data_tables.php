<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32); $table->string('name'); $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->string('tax_number')->nullable(); $table->text('billing_address')->nullable(); $table->decimal('credit_limit', 14, 2)->default(0);
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['organization_id', 'code']);
        });
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32); $table->string('name'); $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->string('tax_number')->nullable(); $table->text('address')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->unique(['organization_id', 'code']);
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name'); $table->string('code', 32); $table->text('description')->nullable(); $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 64); $table->string('name'); $table->text('description')->nullable();
            $table->string('unit', 16)->default('each'); $table->decimal('cost_price', 14, 2)->default(0); $table->decimal('sale_price', 14, 2)->default(0);
            $table->decimal('reorder_level', 14, 3)->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['organization_id', 'sku']);
        });
        Schema::create('warehouses', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 32); $table->string('name'); $table->text('address')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses'); Schema::dropIfExists('products'); Schema::dropIfExists('categories');
        Schema::dropIfExists('suppliers'); Schema::dropIfExists('customers');
    }
};
