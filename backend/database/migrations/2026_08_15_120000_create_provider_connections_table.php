<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('provider_key', 64)->unique();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_test_at')->nullable();
            $table->string('last_test_status', 32)->nullable();
            $table->text('last_test_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};
