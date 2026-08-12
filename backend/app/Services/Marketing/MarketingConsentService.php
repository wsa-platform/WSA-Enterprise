<?php

namespace App\Services\Marketing;

use App\Models\MarketingConsent;
use App\Models\MarketingSuppression;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class MarketingConsentService
{
    public function __construct(private AuditService $auditService) {}

    public function isAllowed(int $organizationId, string $channel, ?User $user = null, ?string $email = null, ?string $phone = null): bool
    {
        $identifier = $email ?? $phone ?? ($user !== null ? (string) $user->id : null);
        if ($identifier === null) {
            return false;
        }

        if (MarketingSuppression::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->where('identifier', $identifier)
            ->exists()) {
            return false;
        }

        $consent = MarketingConsent::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->when($user !== null, fn ($q) => $q->where('user_id', $user->id))
            ->when($user === null && $email !== null, fn ($q) => $q->where('email', $email))
            ->when($user === null && $phone !== null, fn ($q) => $q->where('phone', $phone))
            ->first();

        if ($consent === null) {
            return false;
        }

        return $consent->opted_in && $consent->opted_out_at === null;
    }

    /** @param  array<string, mixed>  $data */
    public function recordConsent(int $organizationId, array $data, ?int $actorUserId = null, ?Request $request = null): MarketingConsent
    {
        $consent = MarketingConsent::updateOrCreate(
            [
                'organization_id' => $organizationId,
                'channel' => $data['channel'],
                'user_id' => $data['user_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ],
            [
                'opted_in' => (bool) ($data['opted_in'] ?? false),
                'opted_out_at' => ($data['opted_in'] ?? false) ? null : now(),
                'source' => $data['source'] ?? 'admin',
            ],
        );

        $this->auditService->record(
            action: 'marketing.consent_updated',
            organizationId: $organizationId,
            userId: $actorUserId,
            auditable: $consent,
            newValues: ['channel' => $consent->channel, 'opted_in' => $consent->opted_in],
            request: $request,
        );

        return $consent;
    }

    public function suppress(int $organizationId, string $channel, string $identifier, ?string $reason, ?int $actorUserId, ?Request $request = null): MarketingSuppression
    {
        $suppression = MarketingSuppression::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'channel' => $channel,
                'identifier' => $identifier,
            ],
            [
                'reason' => $reason,
                'created_by_user_id' => $actorUserId,
            ],
        );

        MarketingConsent::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('channel', $channel)
            ->where(function ($q) use ($identifier): void {
                $q->where('email', $identifier)->orWhere('phone', $identifier)->orWhere('user_id', $identifier);
            })
            ->update(['opted_in' => false, 'opted_out_at' => now()]);

        $this->auditService->record(
            action: 'marketing.suppression_added',
            organizationId: $organizationId,
            userId: $actorUserId,
            auditable: $suppression,
            newValues: ['channel' => $channel, 'identifier' => $identifier],
            request: $request,
        );

        return $suppression;
    }
}
