<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\AuditLog;
use App\Models\JobTalentContact;
use App\Models\JobTalentProfile;
use App\Models\MarketingCampaign;
use App\Models\MarketingConsent;
use App\Models\MarketingDelivery;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\EnterpriseRoleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @group security
 */
class Phase18M18AiMarketingCommunicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
    }

    private function adminHeaders(?Organization $organization = null): array
    {
        $organization ??= Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('phase18-admin')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];
    }

    public function test_ai_assistant_conversation_history_archive_and_delete(): void
    {
        $headers = $this->adminHeaders();

        $created = $this->postJson('/api/v1/ai/assistant/conversations', [
            'domain' => 'platform',
            'message' => 'Summarize my organization access.',
        ], $headers)->assertCreated();

        $conversationId = $created->json('conversation_id');

        $this->getJson("/api/v1/ai/assistant/conversations/{$conversationId}", $headers)
            ->assertOk()
            ->assertJsonPath('messages.0.role', 'user');

        $this->postJson("/api/v1/ai/assistant/conversations/{$conversationId}/archive", [], $headers)->assertOk();
        $this->assertNotNull(AiConversation::withTrashed()->find($conversationId)->archived_at);

        $this->deleteJson("/api/v1/ai/assistant/conversations/{$conversationId}", [], $headers)->assertNoContent();
        $this->assertSoftDeleted('ai_conversations', ['id' => $conversationId]);
    }

    public function test_ai_action_requires_authorization_and_confirmation(): void
    {
        $headers = $this->adminHeaders();

        $this->postJson('/api/v1/ai/assistant/actions/execute', [
            'action_type' => 'create_draft_job',
            'payload' => ['title' => 'Irrigation role'],
            'confirmed' => false,
        ], $headers)->assertStatus(422);

        $this->postJson('/api/v1/ai/assistant/actions/execute', [
            'action_type' => 'create_draft_job',
            'payload' => ['title' => 'Irrigation role'],
            'confirmed' => true,
        ], $headers)->assertOk()->assertJsonPath('status', 'accepted');
    }

    public function test_ai_vision_upload_validation_and_analysis_fallback(): void
    {
        $headers = $this->adminHeaders();

        $upload = $this->post('/api/v1/ai/vision/uploads', [
            'file' => UploadedFile::fake()->create('hive.jpg', 100, 'image/jpeg'),
        ], $headers)->assertCreated();

        $vision = $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'vision_analysis',
            'input' => ['image_path' => $upload->json('storage_path')],
        ], $headers)->assertCreated();

        $content = $vision->json('output.content') ?? $vision->json('output');
        $this->assertTrue(
            ($content['requires_more_information'] ?? false) === true
            || ($content['escalate_to_expert'] ?? false) === true
            || ($content['confidence'] ?? 1) < 0.6,
        );
    }

    public function test_marketing_campaign_lifecycle_with_mock_providers(): void
    {
        $organization = Organization::first();
        $headers = $this->adminHeaders($organization);

        $template = $this->postJson('/api/v1/marketing/templates', [
            'slug' => 'welcome-email',
            'name' => 'Welcome',
            'channel' => 'email',
            'translations' => [
                'en' => ['subject' => 'Welcome', 'body' => 'Hello from WSA'],
                'ar' => ['subject' => 'مرحباً', 'body' => 'مرحباً من WSA'],
            ],
        ], $headers)->assertCreated()->json();

        $segment = $this->postJson('/api/v1/marketing/segments', [
            'name' => 'Members',
            'criteria' => ['user_type' => 'member'],
        ], $headers)->assertCreated()->json();

        $campaign = $this->postJson('/api/v1/marketing/campaigns', [
            'name' => 'Spring outreach',
            'channel' => 'email',
            'template_id' => $template['id'],
            'audience_segment_id' => $segment['id'],
        ], $headers)->assertCreated()->json();

        $this->postJson("/api/v1/marketing/campaigns/{$campaign['id']}/schedule", [], $headers)->assertOk();
        $this->postJson("/api/v1/marketing/campaigns/{$campaign['id']}/test-send", ['locale' => 'en'], $headers)->assertCreated();

        $this->getJson("/api/v1/marketing/campaigns/{$campaign['id']}/preview?locale=ar", $headers)
            ->assertOk()
            ->assertJsonPath('locale', 'ar');
    }

    public function test_marketing_opt_out_blocks_delivery(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $headers = $this->adminHeaders($organization);

        $this->postJson('/api/v1/marketing/consents', [
            'channel' => 'email',
            'user_id' => $admin->id,
            'email' => $admin->email,
            'opted_in' => true,
            'source' => 'test',
        ], $headers)->assertCreated();

        $talent = User::create([
            'name' => 'Opt Out Talent',
            'email' => 'optout@wsa.test',
            'password' => Hash::make('password'),
        ]);
        $organization->members()->syncWithoutDetaching([$talent->id => ['role' => 'member', 'is_active' => true]]);
        EnterpriseRoleService::seedForOrganization($organization->id);

        $profile = JobTalentProfile::create([
            'user_id' => $talent->id,
            'professional_name' => 'Opt Out',
            'employment_status' => 'available',
            'is_public' => true,
        ]);
        JobTalentContact::create([
            'talent_profile_id' => $profile->id,
            'email' => 'optout@wsa.test',
        ]);

        $segment = $this->postJson('/api/v1/marketing/segments', [
            'name' => 'Job seekers',
            'criteria' => ['user_type' => 'job_seeker'],
        ], $headers)->assertCreated()->json();

        $campaign = $this->postJson('/api/v1/marketing/campaigns', [
            'name' => 'Talent outreach',
            'channel' => 'email',
            'audience_segment_id' => $segment['id'],
            'content' => ['subject' => 'Opportunity', 'body' => 'Join our network'],
        ], $headers)->assertCreated()->json();

        $this->postJson('/api/v1/marketing/consents', [
            'channel' => 'email',
            'user_id' => $talent->id,
            'email' => 'optout@wsa.test',
            'opted_in' => false,
            'source' => 'user',
        ], $headers)->assertCreated();

        $this->postJson("/api/v1/marketing/campaigns/{$campaign['id']}/process", [], $headers)->assertOk();

        $this->assertTrue(
            MarketingDelivery::where('campaign_id', $campaign['id'])->where('status', 'rejected')->exists()
        );
        $this->assertFalse(
            MarketingDelivery::where('campaign_id', $campaign['id'])->where('status', 'delivered')->where('recipient_id', $talent->id)->exists()
        );
    }

    public function test_marketing_dashboard_requires_admin_permission(): void
    {
        $organization = Organization::first();
        $member = User::where('email', 'member@wsa.test')->first();
        $token = $member->createToken('phase18-member')->plainTextToken;
        $memberHeaders = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $this->getJson('/api/v1/marketing/dashboard', $memberHeaders)->assertForbidden();
        $this->getJson('/api/v1/marketing/campaigns', $this->adminHeaders($organization))->assertOk();
    }

    public function test_marketing_actions_are_audited(): void
    {
        $headers = $this->adminHeaders();

        $this->postJson('/api/v1/marketing/consents', [
            'channel' => 'sms',
            'email' => 'audited@wsa.test',
            'opted_in' => true,
            'source' => 'admin',
        ], $headers)->assertCreated();

        $this->assertTrue(
            AuditLog::withoutGlobalScopes()->where('action', 'marketing.consent_updated')->exists()
        );
    }

    public function test_m17_regression_still_passes_assistant_endpoint(): void
    {
        $headers = $this->adminHeaders();
        $this->postJson('/api/v1/ai/assistant/conversations', [
            'domain' => 'beekeeping',
            'message' => 'What should I inspect?',
        ], $headers)->assertCreated()->assertJsonStructure(['confidence']);
    }
}
