<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 64);
            $table->unsignedInteger('limit_value')->nullable();
            $table->string('limit_period', 32)->default('monthly');
            $table->timestamps();
            $table->unique(['plan_id', 'feature_key']);
        });

        Schema::create('billing_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider', 32)->default('mock');
            $table->string('external_customer_id')->nullable();
            $table->string('billing_email')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('billing_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 32)->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->date('current_period_start')->nullable();
            $table->date('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->string('external_subscription_id')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 64);
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('external_invoice_id')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'number']);
        });

        Schema::create('billing_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('billing_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('external_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('billing_accounts');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
