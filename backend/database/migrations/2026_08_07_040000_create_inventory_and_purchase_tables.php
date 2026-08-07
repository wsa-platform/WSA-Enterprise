<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0); $table->decimal('reserved_quantity', 14, 3)->default(0); $table->decimal('average_cost', 14, 2)->default(0);
            $table->timestamps(); $table->unique(['warehouse_id', 'product_id']);
        });
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32); $table->decimal('quantity', 14, 3); $table->decimal('unit_cost', 14, 2)->default(0);
            $table->string('reference_type')->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->text('notes')->nullable();
            $table->timestamps(); $table->index(['reference_type', 'reference_id']);
        });
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id(); $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('number', 32); $table->string('status')->default('draft')->index(); $table->date('ordered_at')->nullable(); $table->date('expected_at')->nullable();
            $table->string('currency', 3)->default('USD'); $table->decimal('subtotal', 14, 2)->default(0); $table->decimal('tax_total', 14, 2)->default(0); $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable(); $table->timestamps(); $table->unique(['organization_id', 'number']);
        });
        Schema::create('purchase_order_items', function (Blueprint $table): void {
            $table->id(); $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 14, 3); $table->decimal('received_quantity', 14, 3)->default(0); $table->decimal('unit_cost', 14, 2); $table->decimal('tax_rate', 5, 2)->default(0); $table->decimal('line_total', 14, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_order_items'); Schema::dropIfExists('purchase_orders'); Schema::dropIfExists('inventory_movements'); Schema::dropIfExists('inventory_balances'); }
};
