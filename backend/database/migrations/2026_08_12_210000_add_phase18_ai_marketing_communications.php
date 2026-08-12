<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('context');
            $table->softDeletes();
        });

        Schema::create('ai_vision_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('storage_path');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size_bytes');
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
        });

        Schema::create('marketing_audience_segments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('criteria')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        Schema::create('marketing_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('channel', 32);
            $table->json('translations');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });

        Schema::create('marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('channel', 32);
            $table->foreignId('audience_segment_id')->nullable()->constrained('marketing_audience_segments')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('marketing_templates')->nullOnDelete();
            $table->json('content')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'scheduled_at']);
        });

        Schema::create('marketing_consents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('channel', 32);
            $table->boolean('opted_in')->default(false);
            $table->timestamp('opted_out_at')->nullable();
            $table->string('source', 64)->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'channel', 'user_id']);
            $table->index(['organization_id', 'channel', 'email']);
        });

        Schema::create('marketing_suppressions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32);
            $table->string('identifier');
            $table->string('reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'channel', 'identifier']);
        });

        Schema::create('marketing_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->string('recipient_type', 32)->nullable();
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('channel', 32);
            $table->string('status', 32)->default('queued');
            $table->string('provider', 64)->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_deliveries');
        Schema::dropIfExists('marketing_suppressions');
        Schema::dropIfExists('marketing_consents');
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('marketing_templates');
        Schema::dropIfExists('marketing_audience_segments');
        Schema::dropIfExists('ai_vision_uploads');
        Schema::table('ai_conversations', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn('archived_at');
        });
    }
};
