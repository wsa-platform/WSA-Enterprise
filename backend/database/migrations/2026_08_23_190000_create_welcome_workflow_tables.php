<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('welcome_events')) {
            Schema::create('welcome_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
                $table->string('trigger', 64)->default('registration');
                $table->string('status', 32)->default('pending');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'trigger']);
            });
        }

        if (! Schema::hasTable('welcome_deliveries')) {
            Schema::create('welcome_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('welcome_event_id')->constrained()->cascadeOnDelete();
                $table->string('channel', 32);
                $table->string('status', 32)->default('pending');
                $table->string('provider', 64)->nullable();
                $table->string('provider_message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['welcome_event_id', 'channel']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('welcome_deliveries');
        Schema::dropIfExists('welcome_events');
    }
};
