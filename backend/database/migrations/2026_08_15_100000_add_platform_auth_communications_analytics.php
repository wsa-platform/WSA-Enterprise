<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // welcome_events and welcome_deliveries are created by
        // 2026_08_23_190000_create_welcome_workflow_tables, which may already
        // be applied on environments that received that migration first.
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('source', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['organization_id', 'event_type', 'occurred_at']);
        });

        Schema::create('visitor_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'started_at']);
        });

        Schema::create('page_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visitor_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('referrer')->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->index(['organization_id', 'viewed_at']);
        });

        Schema::create('visitor_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visitor_session_id')->constrained()->cascadeOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_campaign_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->json('metrics');
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->index(['campaign_id', 'captured_at']);
        });

        Schema::create('system_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('component', 64);
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['component', 'checked_at']);
        });

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('level', 16)->default('info');
            $table->string('channel', 64)->nullable();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();

            $table->index(['level', 'logged_at']);
            $table->index(['organization_id', 'logged_at']);
        });

        Schema::create('ai_recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_request_id')->nullable()->constrained('ai_requests')->nullOnDelete();
            $table->string('type', 64);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('system_health_checks');
        Schema::dropIfExists('marketing_campaign_snapshots');
        Schema::dropIfExists('visitor_locations');
        Schema::dropIfExists('page_views');
        Schema::dropIfExists('visitor_sessions');
        Schema::dropIfExists('analytics_events');
    }
};
