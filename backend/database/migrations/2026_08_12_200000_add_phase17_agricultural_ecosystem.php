<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_talent_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('professional_name');
            $table->string('specialization')->nullable();
            $table->text('biography')->nullable();
            $table->string('country', 64)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('certificates')->nullable();
            $table->json('languages')->nullable();
            $table->json('disciplines')->nullable();
            $table->json('work_preferences')->nullable();
            $table->json('availability')->nullable();
            $table->string('employment_status', 32)->default('available');
            $table->string('cv_path')->nullable();
            $table->string('cv_parse_status', 32)->nullable();
            $table->timestamp('cv_parsed_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('employment_status');
            $table->index(['country', 'region', 'city']);
            $table->index('specialization');
        });

        Schema::create('job_talent_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('talent_profile_id')->unique()->constrained('job_talent_profiles')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->json('other_channels')->nullable();
            $table->timestamps();
        });

        Schema::create('job_contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_profile_id')->constrained('job_talent_profiles')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employer_contact_name');
            $table->string('employer_contact_email');
            $table->string('employer_contact_phone', 32)->nullable();
            $table->string('employer_contact_whatsapp', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('job_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['talent_profile_id', 'status']);
        });

        Schema::create('job_contact_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_request_id')->unique()->constrained('job_contact_requests')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_provider', 64)->default('mock');
            $table->string('payment_reference')->nullable();
            $table->string('payment_status', 32)->default('pending');
            $table->string('contact_exchange_status', 32)->default('pending');
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('exchanged_at')->nullable();
            $table->timestamps();

            $table->index('payment_status');
        });

        Schema::create('job_employment_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_profile_id')->constrained('job_talent_profiles')->cascadeOnDelete();
            $table->foreignId('contact_transaction_id')->nullable()->constrained('job_contact_transactions')->nullOnDelete();
            $table->string('job_reference')->nullable();
            $table->string('employment_status', 32)->default('hired');
            $table->timestamp('hired_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['talent_profile_id', 'employment_status']);
        });

        Schema::create('job_moderation_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_type', 64);
            $table->unsignedBigInteger('target_id');
            $table->text('reason');
            $table->string('status', 32)->default('open');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('status');
        });

        Schema::create('beekeeper_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('country', 64)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('hive_count')->default(0);
            $table->unsignedInteger('colony_count')->default(0);
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->json('production_types')->nullable();
            $table->json('goals')->nullable();
            $table->json('seasonal_activity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('apiaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beekeeper_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('country', 64)->nullable();
            $table->string('region', 128)->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('hives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apiary_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('colony_status', 32)->default('active');
            $table->json('queen_info')->nullable();
            $table->unsignedInteger('frame_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['apiary_id', 'code']);
        });

        Schema::create('hive_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at');
            $table->string('overall_status', 32)->default('healthy');
            $table->json('findings')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hive_id', 'inspected_at']);
        });

        Schema::create('hive_treatments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->string('treatment_type');
            $table->timestamp('applied_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hive_feedings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->string('feed_type');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 16)->nullable();
            $table->timestamp('fed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hive_production_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hive_id')->constrained()->cascadeOnDelete();
            $table->string('product_type');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 16)->default('kg');
            $table->timestamp('recorded_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bee_calendar_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apiary_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hive_id')->nullable()->constrained()->nullOnDelete();
            $table->string('task_type');
            $table->string('severity', 16)->default('normal');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('status', 32)->default('pending');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'scheduled_for']);
            $table->index(['organization_id', 'severity', 'status']);
        });

        Schema::create('pollination_plants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('species_name');
            $table->string('common_name')->nullable();
            $table->date('flowering_start')->nullable();
            $table->date('flowering_end')->nullable();
            $table->string('location')->nullable();
            $table->string('country', 64)->nullable();
            $table->string('region', 128)->nullable();
            $table->unsignedTinyInteger('pollination_relevance')->default(5);
            $table->json('expected_seasons')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'flowering_start', 'flowering_end']);
        });

        Schema::create('bee_knowledge_topics', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('title_key');
            $table->string('summary_key')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 64)->default('platform');
            $table->string('title')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
        });

        Schema::create('ai_conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 16);
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
        });

        $now = now();
        DB::table('bee_knowledge_topics')->insert([
            ['slug' => 'honey-bees', 'category' => 'biology', 'title_key' => 'beekeeping.knowledge.honeyBees.title', 'summary_key' => 'beekeeping.knowledge.honeyBees.summary', 'tags' => json_encode(['colony', 'biology']), 'metadata' => json_encode(['rag_ready' => true]), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'varroa', 'category' => 'pests', 'title_key' => 'beekeeping.knowledge.varroa.title', 'summary_key' => 'beekeeping.knowledge.varroa.summary', 'tags' => json_encode(['varroa', 'mites']), 'metadata' => json_encode(['rag_ready' => true]), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'brood-diseases', 'category' => 'health', 'title_key' => 'beekeeping.knowledge.broodDiseases.title', 'summary_key' => 'beekeeping.knowledge.broodDiseases.summary', 'tags' => json_encode(['brood', 'disease']), 'metadata' => json_encode(['rag_ready' => true, 'disclaimer' => 'decision_support_only']), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'nutrition-feeding', 'category' => 'management', 'title_key' => 'beekeeping.knowledge.nutrition.title', 'summary_key' => 'beekeeping.knowledge.nutrition.summary', 'tags' => json_encode(['feeding', 'nutrition']), 'metadata' => json_encode(['rag_ready' => true]), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'pollination', 'category' => 'pollination', 'title_key' => 'beekeeping.knowledge.pollination.title', 'summary_key' => 'beekeeping.knowledge.pollination.summary', 'tags' => json_encode(['pollination', 'plants']), 'metadata' => json_encode(['rag_ready' => true]), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'queen-rearing', 'category' => 'management', 'title_key' => 'beekeeping.knowledge.queenRearing.title', 'summary_key' => 'beekeeping.knowledge.queenRearing.summary', 'tags' => json_encode(['queen', 'breeding']), 'metadata' => json_encode(['rag_ready' => true]), 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('bee_knowledge_topics');
        Schema::dropIfExists('pollination_plants');
        Schema::dropIfExists('bee_calendar_tasks');
        Schema::dropIfExists('hive_production_records');
        Schema::dropIfExists('hive_feedings');
        Schema::dropIfExists('hive_treatments');
        Schema::dropIfExists('hive_inspections');
        Schema::dropIfExists('hives');
        Schema::dropIfExists('apiaries');
        Schema::dropIfExists('beekeeper_profiles');
        Schema::dropIfExists('job_moderation_reports');
        Schema::dropIfExists('job_employment_records');
        Schema::dropIfExists('job_contact_transactions');
        Schema::dropIfExists('job_contact_requests');
        Schema::dropIfExists('job_talent_contacts');
        Schema::dropIfExists('job_talent_profiles');
    }
};
