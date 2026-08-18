<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('normalized_email')->nullable();
            $table->string('normalized_phone', 32)->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'normalized_email']);
            $table->index(['organization_id', 'normalized_phone']);
            $table->index(['organization_id', 'name']);
            $table->unique(['organization_id', 'normalized_email']);
            $table->unique(['organization_id', 'normalized_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
