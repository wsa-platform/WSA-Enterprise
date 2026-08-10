<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToOrganization;

    /** @var list<string> */
    public const ACTIVE_STATUSES = ['trialing', 'active'];

    protected $fillable = [
        'organization_id',
        'plan_id',
        'billing_account_id',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancel_at_period_end',
        'external_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'current_period_start' => 'date',
            'current_period_end' => 'date',
            'cancelled_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class);
    }

    public function isActive(): bool
    {
        if (in_array($this->status, self::ACTIVE_STATUSES, true)) {
            return true;
        }

        return $this->status === 'cancelled'
            && $this->cancel_at_period_end
            && $this->current_period_end !== null
            && $this->current_period_end->isFuture();
    }
}
