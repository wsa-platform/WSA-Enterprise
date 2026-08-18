<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_lists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        Schema::create('communication_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('channel', 32);
            $table->string('status', 32)->default('draft');
            $table->boolean('is_bulk')->default(false);
            $table->boolean('is_newsletter')->default(false);
            $table->foreignId('mailing_list_id')->nullable()->constrained('mailing_lists')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'created_at']);
        });

        Schema::create('mailing_list_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->timestamps();

            $table->index(['mailing_list_id', 'email']);
        });

        Schema::create('communication_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('communication_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['communication_message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_recipients');
        Schema::dropIfExists('mailing_list_members');
        Schema::dropIfExists('communication_messages');
        Schema::dropIfExists('mailing_lists');
    }
};
