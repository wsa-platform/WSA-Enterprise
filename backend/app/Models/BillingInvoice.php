<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'subscription_id',
        'number',
        'status',
        'amount_cents',
        'currency',
        'period_start',
        'period_end',
        'due_at',
        'paid_at',
        'external_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }
}
