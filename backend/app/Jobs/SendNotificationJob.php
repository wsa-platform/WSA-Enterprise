<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $deliveryId)
    {
        $this->onQueue(config('notifications.queue', 'default'));
    }

    public function handle(): void
    {
        $delivery = NotificationDelivery::withoutGlobalScopes()->find($this->deliveryId);

        if ($delivery === null || $delivery->status === 'sent') {
            return;
        }

        try {
            match ($delivery->channel) {
                'email' => $this->deliverEmail($delivery),
                default => $this->markSent($delivery),
            };
        } catch (Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function deliverEmail(NotificationDelivery $delivery): void
    {
        if (! config('notifications.channels.email', false)) {
            Log::info('notification.email.skipped', [
                'delivery_id' => $delivery->id,
                'organization_id' => $delivery->organization_id,
                'reason' => 'email channel disabled',
            ]);
            $this->markSent($delivery);

            return;
        }

        Log::info('notification.email.sent', [
            'delivery_id' => $delivery->id,
            'organization_id' => $delivery->organization_id,
            'payload' => $delivery->payload,
        ]);

        $this->markSent($delivery);
    }

    private function markSent(NotificationDelivery $delivery): void
    {
        $delivery->update([
            'status' => 'sent',
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }
}
