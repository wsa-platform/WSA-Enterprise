<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number',32); $table->string('status')->default('draft')->index(); $table->date('ordered_at')->nullable(); $table->date('expected_at')->nullable(); $table->string('currency',3)->default('USD');
            $table->decimal('subtotal',14,2)->default(0); $table->decimal('tax_total',14,2)->default(0); $table->decimal('total',14,2)->default(0); $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['organization_id','number']);
        });
        Schema::create('sales_order_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->restrictOnDelete(); $table->decimal('quantity',14,3); $table->decimal('fulfilled_quantity',14,3)->default(0); $table->decimal('unit_price',14,2); $table->decimal('tax_rate',5,2)->default(0); $table->decimal('line_total',14,2); $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('customer_id')->constrained()->restrictOnDelete(); $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number',32); $table->string('status')->default('draft')->index(); $table->date('issued_at')->nullable(); $table->date('due_at')->nullable(); $table->timestamp('paid_at')->nullable(); $table->string('currency',3)->default('USD');
            $table->decimal('subtotal',14,2)->default(0); $table->decimal('tax_total',14,2)->default(0); $table->decimal('total',14,2)->default(0); $table->timestamps(); $table->unique(['organization_id','number']);
        });
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('invoice_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); $table->string('description'); $table->decimal('quantity',14,3); $table->decimal('unit_price',14,2); $table->decimal('tax_rate',5,2)->default(0); $table->decimal('line_total',14,2); $table->timestamps();
        });
        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); $table->string('type',64)->default('info'); $table->string('title'); $table->text('body')->nullable(); $table->json('data')->nullable(); $table->timestamp('read_at')->nullable(); $table->timestamps(); $table->index(['organization_id','read_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('app_notifications'); Schema::dropIfExists('invoice_items'); Schema::dropIfExists('invoices'); Schema::dropIfExists('sales_order_items'); Schema::dropIfExists('sales_orders'); }
};
