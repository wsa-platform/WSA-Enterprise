<?php

namespace Tests\Feature;

use App\Models\CommunicationMessage;
use App\Models\Contact;
use App\Models\MailingList;
use App\Models\MailingListMember;
use App\Models\MarketingSuppression;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunicationsCenterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@wsa.test')->firstOrFail();
        $this->organization = Organization::where('slug', 'wsa-demo')->firstOrFail();

        config([
            'marketing.providers.email' => 'mock',
            'marketing.providers.sms' => 'mock',
            'marketing.providers.whatsapp' => 'mock',
        ]);
    }

    private function headers(): array
    {
        return ['X-Organization-Id' => (string) $this->organization->id];
    }

    public function test_contacts_search_returns_matches(): void
    {
        Contact::create([
            'organization_id' => $this->organization->id,
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'normalized_email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/contacts/search?q=ahmed')
            ->assertOk()
            ->assertJsonPath('data.0.email', 'ahmed@example.com');
    }

    public function test_compose_preview_returns_recipient_count(): void
    {
        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->postJson('/api/v1/communications/messages', [
                'subject' => 'معاينة',
                'body' => 'نص الرسالة',
                'channel' => 'email',
                'recipient_mode' => 'individual',
                'recipients' => [['email' => 'test@example.com']],
                'preview_only' => true,
            ])
            ->assertOk()
            ->assertJsonPath('preview', true)
            ->assertJsonPath('recipient_count', 1);
    }

    public function test_draft_save_and_load(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->withHeaders($this->headers())
            ->postJson('/api/v1/communications/messages', [
                'subject' => 'مسودة',
                'body' => 'محتوى المسودة',
                'channel' => 'email',
                'recipient_mode' => 'individual',
                'recipients' => [['email' => 'draft@example.com']],
            ])
            ->assertCreated()
            ->json();

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/drafts')
            ->assertOk()
            ->assertJsonFragment(['subject' => 'مسودة']);

        $this->withHeaders($this->headers())
            ->patchJson("/api/v1/communications/messages/{$created['id']}", [
                'subject' => 'مسودة محدّثة',
            ])
            ->assertOk()
            ->assertJsonPath('subject', 'مسودة محدّثة');
    }

    public function test_mailing_list_bulk_preview_includes_member_count(): void
    {
        $list = MailingList::create([
            'organization_id' => $this->organization->id,
            'name' => 'قائمة اختبار',
        ]);
        MailingListMember::create(['mailing_list_id' => $list->id, 'email' => 'a@test.com']);
        MailingListMember::create(['mailing_list_id' => $list->id, 'email' => 'b@test.com']);

        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->postJson('/api/v1/communications/messages', [
                'subject' => 'جماعي',
                'body' => 'رسالة جماعية',
                'channel' => 'email',
                'recipient_mode' => 'bulk',
                'mailing_list_id' => $list->id,
                'is_bulk' => true,
                'preview_only' => true,
            ])
            ->assertOk()
            ->assertJsonPath('recipient_count', 2)
            ->assertJsonPath('mailing_list_member_count', 2);
    }

    public function test_providers_returns_only_connected_channels(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/providers')
            ->assertOk()
            ->json('providers');

        $this->assertArrayHasKey('email', $response);
        $this->assertTrue($response['email']['connected']);
    }

    public function test_send_saves_contact_after_success_when_requested(): void
    {
        Sanctum::actingAs($this->admin);

        $message = CommunicationMessage::create([
            'organization_id' => $this->organization->id,
            'created_by_user_id' => $this->admin->id,
            'subject' => 'اختبار',
            'body' => 'مرحباً',
            'channel' => 'email',
            'status' => 'draft',
        ]);
        $message->recipients()->create([
            'email' => 'saved@example.com',
            'status' => 'pending',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/communications/messages/{$message->id}/send", [
                'save_contact' => true,
                'contact_name' => 'مستخدم جديد',
                'contact_email' => 'saved@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('saved_contact', true);

        $this->assertDatabaseHas('contacts', [
            'organization_id' => $this->organization->id,
            'normalized_email' => 'saved@example.com',
            'name' => 'مستخدم جديد',
        ]);
    }

    public function test_suppressed_recipient_fails_send(): void
    {
        MarketingSuppression::create([
            'organization_id' => $this->organization->id,
            'channel' => 'email',
            'identifier' => 'blocked@example.com',
            'reason' => 'opt-out',
        ]);

        Sanctum::actingAs($this->admin);

        $message = CommunicationMessage::create([
            'organization_id' => $this->organization->id,
            'created_by_user_id' => $this->admin->id,
            'subject' => 'اختبار',
            'body' => 'مرحباً',
            'channel' => 'email',
            'status' => 'draft',
        ]);
        $message->recipients()->create([
            'email' => 'blocked@example.com',
            'status' => 'pending',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/communications/messages/{$message->id}/send")
            ->assertOk()
            ->assertJsonPath('delivery_stats.sent', 0)
            ->assertJsonPath('delivery_stats.failed', 1);
    }

    public function test_messages_filter_by_status(): void
    {
        CommunicationMessage::create([
            'organization_id' => $this->organization->id,
            'subject' => 'Draft msg',
            'body' => 'body',
            'channel' => 'email',
            'status' => 'draft',
        ]);
        CommunicationMessage::create([
            'organization_id' => $this->organization->id,
            'subject' => 'Sent msg',
            'body' => 'body',
            'channel' => 'email',
            'status' => 'sent',
        ]);

        Sanctum::actingAs($this->admin);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/messages?status=sent')
            ->assertOk()
            ->assertJsonFragment(['subject' => 'Sent msg'])
            ->assertJsonMissing(['subject' => 'Draft msg']);
    }

    public function test_contacts_crud(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->withHeaders($this->headers())
            ->postJson('/api/v1/communications/contacts', [
                'name' => 'سارة',
                'email' => 'sara@example.com',
            ])
            ->assertCreated()
            ->json();

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/communications/contacts')
            ->assertOk()
            ->assertJsonFragment(['email' => 'sara@example.com']);

        $this->withHeaders($this->headers())
            ->patchJson("/api/v1/communications/contacts/{$created['id']}", [
                'name' => 'سارة محدّثة',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'سارة محدّثة');

        $this->withHeaders($this->headers())
            ->deleteJson("/api/v1/communications/contacts/{$created['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('contacts', ['id' => $created['id']]);
    }

    public function test_communications_routes_are_documented_in_openapi(): void
    {
        $content = file_get_contents($this->openApiSpecPath());
        $this->assertNotFalse($content);

        $required = [
            '/communications/inbox',
            '/communications/contacts',
            '/communications/contacts/search',
            '/communications/contacts/{contact}',
            '/communications/drafts',
            '/communications/messages',
            '/communications/messages/{message}',
            '/communications/messages/{message}/send',
            '/communications/providers',
            '/communications/mailing-lists',
        ];

        foreach ($required as $path) {
            $this->assertStringContainsString(
                "  {$path}:",
                $content,
                "Missing OpenAPI path {$path}"
            );
        }
    }
}
