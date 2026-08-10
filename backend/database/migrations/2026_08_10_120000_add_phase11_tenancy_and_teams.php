<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('slug', 64)->nullable()->after('organization_id');
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('team_user', function (Blueprint $table): void {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('member');
            $table->timestamps();
            $table->primary(['team_id', 'user_id']);
        });

        Schema::create('organization_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_settings');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('teams');

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['organization_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
