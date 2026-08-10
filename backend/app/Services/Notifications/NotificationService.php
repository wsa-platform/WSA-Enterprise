<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\AppNotification;
use App\Models\NotificationDelivery;
use App\Models\User;

class NotificationService
{
    /** @param  array<string, mixed>  $data */
    public function notify(
        int $organizationId,
        ?int $userId,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): AppNotification {
        if (! config('notifications.enabled', true)) {
            return AppNotification::withoutGlobalScopes()->create([
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        $notification = AppNotification::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $this->queueDelivery($organizationId, $notification->id, 'in_app', [
            'notification_id' => $notification->id,
            'type' => $type,
        ]);

        if (config('notifications.channels.email', false)) {
            $this->queueDelivery($organizationId, $notification->id, 'email', [
                'notification_id' => $notification->id,
                'type' => $type,
                'title' => $title,
            ]);
        }

        return $notification;
    }

    public function notifyAiCompleted(int $organizationId, ?int $userId, int $aiRequestId, string $requestType): AppNotification
    {
        return $this->notify(
            organizationId: $organizationId,
            userId: $userId,
            type: 'ai.request.completed',
            title: 'AI request completed',
            body: sprintf('Your %s request (#%d) completed successfully.', $requestType, $aiRequestId),
            data: [
                'ai_request_id' => $aiRequestId,
                'request_type' => $requestType,
            ],
        );
    }

    public function notifyAiFailed(int $organizationId, ?int $userId, int $aiRequestId, string $requestType, ?string $errorMessage = null): AppNotification
    {
        return $this->notify(
            organizationId: $organizationId,
            userId: $userId,
            type: 'ai.request.failed',
            title: 'AI request failed',
            body: sprintf('Your %s request (#%d) failed.', $requestType, $aiRequestId),
            data: [
                'ai_request_id' => $aiRequestId,
                'request_type' => $requestType,
                'error_message' => $errorMessage,
            ],
        );
    }

    /** Security notification foundation — alerts the affected user. */
    public function notifySecurityEvent(
        int $organizationId,
        ?int $userId,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): AppNotification {
        return $this->notify(
            organizationId: $organizationId,
            userId: $userId,
            type: $type,
            title: $title,
            body: $body,
            data: array_merge(['severity' => 'security'], $data),
        );
    }

    public function notifyCrossTenantAttempt(int $organizationId, int $userId, int $attemptedOrganizationId, ?string $requestId = null): AppNotification
    {
        return $this->notifySecurityEvent(
            organizationId: $organizationId,
            userId: $userId,
            type: 'security.cross_tenant_attempt',
            title: 'Cross-organization access blocked',
            body: 'An attempt to access another organization was blocked for your account.',
            data: [
                'attempted_organization_id' => $attemptedOrganizationId,
                'request_id' => $requestId,
            ],
        );
    }

    public function notifyOrganizationAdmins(
        int $organizationId,
        string $type,
        string $title,
        string $body,
        array $data = [],
    ): void {
        User::query()
            ->whereHas('organizations', fn ($query) => $query->whereKey($organizationId)->where('organization_user.role', 'admin'))
            ->each(function (User $admin) use ($organizationId, $type, $title, $body, $data): void {
                $this->notify($organizationId, $admin->id, $type, $title, $body, $data);
            });
    }

    /** @param  array<string, mixed>  $payload */
    private function queueDelivery(int $organizationId, int $notificationId, string $channel, array $payload): NotificationDelivery
    {
        $delivery = NotificationDelivery::withoutGlobalScopes()->create([
            'organization_id' => $organizationId,
            'app_notification_id' => $notificationId,
            'channel' => $channel,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        if (config('queue.default') === 'sync') {
            SendNotificationJob::dispatchSync($delivery->id);
        } else {
            SendNotificationJob::dispatch($delivery->id);
        }

        return $delivery;
    }
}
