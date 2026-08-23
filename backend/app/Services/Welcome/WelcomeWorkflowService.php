<?php

namespace App\Services\Welcome;

use App\Jobs\ProcessWelcomeEventJob;
use App\Models\User;
use App\Models\WelcomeEvent;
use App\Services\Marketing\MarketingProviderResolver;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

class WelcomeWorkflowService
{
    public function __construct(
        private NotificationService $notifications,
        private MarketingProviderResolver $providers,
    ) {}

    public function dispatchRegistrationWelcome(User $user, ?int $organizationId): WelcomeEvent
    {
        return DB::transaction(function () use ($user, $organizationId): WelcomeEvent {
            $event = WelcomeEvent::firstOrCreate(
                ['user_id' => $user->id, 'trigger' => 'registration'],
                [
                    'organization_id' => $organizationId,
                    'status' => WelcomeEvent::STATUS_PENDING,
                ],
            );

            if ($event->wasRecentlyCreated || $event->status === WelcomeEvent::STATUS_PENDING) {
                ProcessWelcomeEventJob::dispatch($event->id);
            }

            return $event;
        });
    }

    public function dispatchSubscriptionWelcome(User $user, int $organizationId, string $planName): void
    {
        $event = WelcomeEvent::firstOrCreate(
            ['user_id' => $user->id, 'trigger' => 'subscription:'.$planName],
            [
                'organization_id' => $organizationId,
                'status' => WelcomeEvent::STATUS_PENDING,
            ],
        );

        if ($event->wasRecentlyCreated) {
            ProcessWelcomeEventJob::dispatch($event->id);
        }
    }

    public function process(WelcomeEvent $event): void
    {
        if ($event->status === WelcomeEvent::STATUS_COMPLETED) {
            return;
        }

        $event->update(['status' => WelcomeEvent::STATUS_PROCESSING]);
        $user = $event->user;
        $organizationId = $event->organization_id;

        $title = str_starts_with($event->trigger, 'subscription:')
            ? 'مرحباً بخطتك الجديدة'
            : 'مرحباً بك في WSA Enterprise';
        $body = $this->buildMessageBody($user, $event->trigger);

        if ($organizationId !== null) {
            $this->notifications->notify(
                organizationId: $organizationId,
                userId: $user->id,
                type: 'welcome.'.$event->trigger,
                title: $title,
                body: $body,
                data: ['welcome_event_id' => $event->id],
            );
        }

        $this->recordDelivery($event, 'in_app', 'internal', true, null);
        $this->attemptEmailDelivery($event, $user, $title, $body);
        $this->attemptSmsDelivery($event, $user, $body);

        $event->update([
            'status' => WelcomeEvent::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);
    }

    private function attemptEmailDelivery(WelcomeEvent $event, User $user, string $title, string $body): void
    {
        if ($user->email === null || $user->email === '') {
            $this->recordDelivery($event, 'email', 'none', false, 'لا يوجد بريد إلكتروني');

            return;
        }

        $result = $this->providers->email()->send($user->email, $title, $body);
        $this->recordDelivery(
            $event,
            'email',
            $result->provider,
            $result->success,
            $result->success ? null : ($result->errorMessage ?? 'email_failed'),
            $result->providerMessageId,
        );
    }

    private function attemptSmsDelivery(WelcomeEvent $event, User $user, string $body): void
    {
        $phone = $user->identities()
            ->where('provider', \App\Models\UserIdentity::PROVIDER_PHONE)
            ->value('phone');
        if ($phone === null || $phone === '') {
            $this->recordDelivery($event, 'sms', 'none', false, 'لا يوجد رقم هاتف');

            return;
        }

        $result = $this->providers->sms()->send($phone, $body);
        $this->recordDelivery(
            $event,
            'sms',
            $result->provider,
            $result->success,
            $result->success ? null : ($result->errorMessage ?? 'sms_failed'),
            $result->providerMessageId,
        );
    }

    private function recordDelivery(
        WelcomeEvent $event,
        string $channel,
        string $provider,
        bool $success,
        ?string $errorMessage,
        ?string $providerMessageId = null,
    ): void {
        $event->deliveries()->create([
            'channel' => $channel,
            'status' => $success ? 'sent' : 'failed',
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'error_message' => $errorMessage,
            'sent_at' => $success ? now() : null,
        ]);
    }

    private function buildMessageBody(User $user, string $trigger): string
    {
        $verifiedName = $user->email_verified_at !== null ? $user->name : null;

        if (str_starts_with($trigger, 'subscription:')) {
            $plan = str_replace('subscription:', '', $trigger);

            return $verifiedName
                ? sprintf('مرحباً %s! تم تفعيل خطة %s بنجاح.', $verifiedName, $plan)
                : sprintf('تم تفعيل خطة %s بنجاح.', $plan);
        }

        return $verifiedName
            ? sprintf('مرحباً %s! شكراً لتسجيلك في WSA Enterprise.', $verifiedName)
            : 'مرحباً! شكراً لتسجيلك في WSA Enterprise.';
    }
}
