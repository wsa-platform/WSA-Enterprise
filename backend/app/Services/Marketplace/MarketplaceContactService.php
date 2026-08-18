<?php

namespace App\Services\Marketplace;

use App\Contracts\MarketplacePaymentProviderInterface;
use App\Models\ContactAccessOrder;
use App\Models\MarketplaceEntitlement;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;

class MarketplaceContactService
{
    public function __construct(
        private MarketplacePaymentProviderInterface $paymentProvider,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    public function requestAccess(User $buyer, MarketplaceListing $listing, ?string $idempotencyKey = null): ContactAccessOrder
    {
        abort_unless($listing->status === MarketplaceListing::STATUS_PUBLISHED, 422, 'Listing is not published.');
        abort_unless($listing->seller_user_id !== $buyer->id, 422, 'Cannot request contact for your own listing.');

        $existing = MarketplaceEntitlement::query()
            ->where('buyer_user_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            abort(422, 'Contact access already granted.');
        }

        if ($idempotencyKey) {
            $existingOrder = ContactAccessOrder::where('idempotency_key', $idempotencyKey)->first();
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        return ContactAccessOrder::create([
            'buyer_user_id' => $buyer->id,
            'seller_user_id' => $listing->seller_user_id,
            'listing_id' => $listing->id,
            'amount' => $listing->contact_access_price,
            'currency' => $listing->contact_access_currency,
            'payment_status' => ContactAccessOrder::PAYMENT_PENDING,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    public function payOrder(ContactAccessOrder $order, string $idempotencyKey, ?int $organizationId, ?Request $request = null): array
    {
        abort_unless($order->payment_status === ContactAccessOrder::PAYMENT_PENDING, 422);

        $order = $this->paymentProvider->charge($order, $idempotencyKey);

        if ($order->payment_status !== ContactAccessOrder::PAYMENT_PAID) {
            $this->audit->record(
                'marketplace.contact_payment_failed',
                $organizationId,
                $order->buyer_user_id,
                $order,
                null,
                ['payment_status' => $order->payment_status],
                $request,
            );

            return ['order' => $order, 'contact' => null];
        }

        $entitlement = MarketplaceEntitlement::updateOrCreate(
            ['buyer_user_id' => $order->buyer_user_id, 'listing_id' => $order->listing_id],
            [
                'order_id' => $order->id,
                'granted_at' => now(),
                'revoked_at' => null,
            ],
        );

        $listing = $order->listing;
        $contact = [
            'seller_email' => $listing->seller_email,
            'seller_phone' => $listing->seller_phone,
            'seller_display_name' => $listing->seller_display_name,
        ];

        $this->audit->record(
            'marketplace.contact_access_granted',
            $organizationId,
            $order->buyer_user_id,
            $entitlement,
            null,
            ['listing_id' => $order->listing_id],
            $request,
        );

        if ($organizationId !== null) {
            $this->notifications->notify(
                organizationId: $organizationId,
                userId: $order->buyer_user_id,
                type: 'marketplace.contact_access_granted',
                title: 'تم منح الوصول لبيانات التواصل',
                body: sprintf('يمكنك الآن التواصل مع البائع لإعلان "%s".', $listing->title),
                data: ['listing_id' => $listing->id, 'entitlement_id' => $entitlement->id],
            );
        }

        return ['order' => $order->fresh(), 'contact' => $contact, 'entitlement' => $entitlement];
    }

    public function hasEntitlement(User $buyer, MarketplaceListing $listing): bool
    {
        return MarketplaceEntitlement::query()
            ->where('buyer_user_id', $buyer->id)
            ->where('listing_id', $listing->id)
            ->whereNull('revoked_at')
            ->exists();
    }

    /** @return array<string, mixed>|null */
    public function contactIfEntitled(User $buyer, MarketplaceListing $listing): ?array
    {
        if (! $this->hasEntitlement($buyer, $listing)) {
            return null;
        }

        return [
            'seller_email' => $listing->seller_email,
            'seller_phone' => $listing->seller_phone,
            'seller_display_name' => $listing->seller_display_name,
        ];
    }
}
