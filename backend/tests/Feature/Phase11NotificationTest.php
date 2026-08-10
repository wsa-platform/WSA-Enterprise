<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\AppNotification;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class Phase11NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_ai_completion_creates_in_app_notification(): void
    {
        Queue::fake();

        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/ai/requests', [
            'request_type' => 'library_summary',
            'input' => ['content' => 'Notification test content'],
        ], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertCreated();

        $this->assertDatabaseHas('app_notifications', [
            'organization_id' => $organization->id,
            'type' => 'ai.request.completed',
        ]);

        $notification = AppNotification::where('type', 'ai.request.completed')->first();
        $this->assertNotNull($notification);

        $this->assertDatabaseHas('notification_deliveries', [
            'organization_id' => $organization->id,
            'app_notification_id' => $notification->id,
            'channel' => 'in_app',
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_notifications_can_be_listed_and_marked_read(): void
    {
        $organization = Organization::first();
        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;
        $headers = [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ];

        $notification = AppNotification::create([
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'type' => 'system.maintenance',
            'title' => 'Maintenance window',
            'body' => 'Scheduled maintenance tonight.',
        ]);

        $this->getJson('/api/v1/notifications', $headers)
            ->assertOk()
            ->assertJsonFragment(['title' => 'Maintenance window']);

        $this->postJson("/api/v1/notifications/{$notification->id}/read", [], $headers)
            ->assertOk()
            ->assertJsonPath('read_at', fn ($value) => $value !== null);
    }

    public function test_foreign_organization_cannot_mark_notification_read(): void
    {
        $organization = Organization::first();
        $foreign = Organization::create(['name' => 'Notification Foreign Org', 'slug' => 'notification-foreign-org']);

        $notification = AppNotification::withoutGlobalScopes()->create([
            'organization_id' => $foreign->id,
            'type' => 'system.maintenance',
            'title' => 'Foreign only',
            'body' => 'Should not be readable.',
        ]);

        $admin = User::where('email', 'admin@wsa.test')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $this->postJson("/api/v1/notifications/{$notification->id}/read", [], [
            'Authorization' => "Bearer {$token}",
            'X-Organization-Id' => (string) $organization->id,
        ])->assertNotFound();
    }

    public function test_send_notification_job_marks_delivery_sent(): void
    {
        $organization = Organization::first();

        $notification = AppNotification::create([
            'organization_id' => $organization->id,
            'type' => 'ai.request.completed',
            'title' => 'Completed',
            'body' => 'Done',
        ]);

        $delivery = NotificationDelivery::create([
            'organization_id' => $organization->id,
            'app_notification_id' => $notification->id,
            'channel' => 'in_app',
            'status' => 'pending',
        ]);

        (new SendNotificationJob($delivery->id))->handle();

        $this->assertDatabaseHas('notification_deliveries', [
            'id' => $delivery->id,
            'status' => 'sent',
        ]);
    }
}
