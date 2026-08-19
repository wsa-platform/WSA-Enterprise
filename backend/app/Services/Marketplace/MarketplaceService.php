<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceCategory;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceListingStatusHistory;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class MarketplaceService
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
    ) {}

    /** @param  array<string, mixed>  $filters */
    public function searchPublished(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        // Published catalog is public. Seller/admin management queries must use searchAdmin / my-listings with organization_id.
        $query = MarketplaceListing::query()
            ->with(['category:id,slug,name,name_ar', 'images'])
            ->where('status', MarketplaceListing::STATUS_PUBLISHED);

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (! empty($filters['seller_type'])) {
            $query->where('seller_type', $filters['seller_type']);
        }
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        return $query->latest('published_at')->paginate(min(max($perPage, 1), 100));
    }

    /** @param  array<string, mixed>  $filters */
    public function searchAdmin(array $filters, int $perPage = 15, ?int $organizationId = null): LengthAwarePaginator
    {
        abort_unless($organizationId !== null, 403, 'Organization context is required.');

        $query = MarketplaceListing::query()
            ->with(['category:id,slug,name,name_ar', 'seller:id,name,email', 'images'])
            ->where('organization_id', $organizationId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['seller_type'])) {
            $query->where('seller_type', $filters['seller_type']);
        }
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where('title', 'like', "%{$term}%");
        }

        return $query->latest()->paginate(min(max($perPage, 1), 100));
    }

    /** @param  array<string, mixed>  $data */
    public function createForSeller(User $seller, array $data, ?int $organizationId = null): MarketplaceListing
    {
        abort_unless($organizationId !== null, 403, 'Organization context is required.');

        $listing = MarketplaceListing::create([
            ...$data,
            'seller_user_id' => $seller->id,
            'organization_id' => $organizationId,
            'status' => MarketplaceListing::STATUS_DRAFT,
            'seller_display_name' => $data['seller_display_name'] ?? $seller->name,
            'contact_access_price' => $data['contact_access_price']
                ?? config('marketplace.contact_access.default_price'),
            'contact_access_currency' => $data['contact_access_currency']
                ?? config('marketplace.contact_access.currency'),
        ]);

        $this->recordStatusChange($listing, MarketplaceListing::STATUS_DRAFT, $seller->id, 'Listing created');

        return $listing->fresh(['category', 'images']);
    }

    /** @param  array<string, mixed>  $data */
    public function updateListing(MarketplaceListing $listing, array $data, User $actor): MarketplaceListing
    {
        $listing->update($data);

        return $listing->fresh(['category', 'images']);
    }

    public function submitForReview(MarketplaceListing $listing, User $actor, ?int $organizationId, ?Request $request = null): MarketplaceListing
    {
        abort_unless(in_array($listing->status, [
            MarketplaceListing::STATUS_DRAFT,
            MarketplaceListing::STATUS_REJECTED,
            MarketplaceListing::STATUS_UNPUBLISHED,
        ], true), 422);
        abort_unless($listing->seller_user_id === $actor->id, 403);

        $listing->update(['status' => MarketplaceListing::STATUS_PENDING_REVIEW]);
        $this->recordStatusChange($listing, MarketplaceListing::STATUS_PENDING_REVIEW, $actor->id, 'Submitted for review');

        $this->audit->record(
            'marketplace.listing_submitted',
            $organizationId,
            $actor->id,
            $listing,
            null,
            ['status' => MarketplaceListing::STATUS_PENDING_REVIEW],
            $request,
        );

        if ($organizationId !== null) {
            $this->notifications->notifyOrganizationAdmins(
                $organizationId,
                'marketplace.listing_pending_review',
                'إعلان جديد بانتظار المراجعة',
                sprintf('الإعلان "%s" بانتظار الموافقة.', $listing->title),
                ['listing_id' => $listing->id],
            );
        }

        return $listing->fresh(['category', 'images']);
    }

    public function unpublish(MarketplaceListing $listing, User $actor, ?int $organizationId, ?Request $request = null): MarketplaceListing
    {
        abort_unless($listing->status === MarketplaceListing::STATUS_PUBLISHED, 422);
        abort_unless($listing->seller_user_id === $actor->id
            || app(\App\Services\Authorization\PermissionService::class)->userCan($actor, $organizationId, 'market.manage_all')
            || app(\App\Services\Ownership\ServiceOwnershipAuthorizer::class)->canSupervise($actor, $organizationId), 403);

        $listing->update([
            'status' => MarketplaceListing::STATUS_UNPUBLISHED,
        ]);
        $this->recordStatusChange($listing, MarketplaceListing::STATUS_UNPUBLISHED, $actor->id, 'Unpublished by owner');

        $this->audit->record(
            'marketplace.listing_unpublished',
            $organizationId,
            $actor->id,
            $listing,
            ['status' => MarketplaceListing::STATUS_PUBLISHED],
            ['status' => MarketplaceListing::STATUS_UNPUBLISHED],
            $request,
        );

        return $listing->fresh(['category', 'images']);
    }

    public function moderate(
        MarketplaceListing $listing,
        string $action,
        User $actor,
        ?string $reason,
        ?int $organizationId,
        ?Request $request = null,
    ): MarketplaceListing {
        abort_unless(
            $organizationId !== null && (int) $listing->organization_id === $organizationId,
            404,
        );

        $newStatus = match ($action) {
            'approve' => MarketplaceListing::STATUS_PUBLISHED,
            'reject' => MarketplaceListing::STATUS_REJECTED,
            'suspend' => MarketplaceListing::STATUS_SUSPENDED,
            default => abort(422, 'Invalid moderation action.'),
        };

        $oldStatus = $listing->status;
        $listing->update([
            'status' => $newStatus,
            'published_at' => $newStatus === MarketplaceListing::STATUS_PUBLISHED ? now() : $listing->published_at,
        ]);

        $this->recordStatusChange($listing, $newStatus, $actor->id, $reason);
        $this->audit->record(
            'marketplace.listing_'.$action,
            $organizationId,
            $actor->id,
            $listing,
            ['status' => $oldStatus],
            ['status' => $newStatus, 'reason' => $reason],
            $request,
        );

        if ($organizationId !== null) {
            $this->notifications->notify(
                organizationId: $organizationId,
                userId: $listing->seller_user_id,
                type: 'marketplace.listing_'.$action,
                title: 'تحديث حالة الإعلان',
                body: sprintf('تم %s إعلانك "%s".', $action, $listing->title),
                data: ['listing_id' => $listing->id, 'status' => $newStatus],
            );
        }

        return $listing->fresh(['category', 'images']);
    }

    public function recordStatusChange(MarketplaceListing $listing, string $status, ?int $userId, ?string $reason): void
    {
        MarketplaceListingStatusHistory::create([
            'listing_id' => $listing->id,
            'status' => $status,
            'changed_by_user_id' => $userId,
            'reason' => $reason,
        ]);
    }

    /** @return list<MarketplaceCategory> */
    public function activeCategories(): array
    {
        return MarketplaceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }
}
