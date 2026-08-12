<?php

use App\Models\Organization;
use App\Services\Authorization\EnterpriseRoleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();
            $table->string('role', 32)->default('member');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'email']);
            $table->index(['organization_id', 'accepted_at']);
        });

        Organization::query()->each(function (Organization $organization): void {
            EnterpriseRoleService::seedForOrganization($organization->id);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invitations');
    }
};
