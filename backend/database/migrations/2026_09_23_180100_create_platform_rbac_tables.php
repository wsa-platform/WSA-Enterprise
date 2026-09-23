<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_permission_platform_role', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_permission_id')->constrained('platform_permissions')->cascadeOnDelete();
            $table->foreignId('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->unique(['platform_permission_id', 'platform_role_id'], 'platform_perm_role_unique');
        });

        Schema::create('platform_role_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('platform_role_id')->constrained('platform_roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['platform_role_id', 'user_id']);
        });

        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('platform_role_user');
        Schema::dropIfExists('platform_permission_platform_role');
        Schema::dropIfExists('platform_roles');
        Schema::dropIfExists('platform_permissions');
    }
};
