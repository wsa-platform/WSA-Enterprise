<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobContactTransaction extends Model
{
    protected $fillable = [
        'contact_request_id',
        'amount',
        'currency',
        'payment_provider',
        'payment_reference',
        'payment_status',
        'contact_exchange_status',
        'idempotency_key',
        'exchanged_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchanged_at' => 'datetime',
        ];
    }

    public function contactRequest(): BelongsTo
    {
        return $this->belongsTo(JobContactRequest::class, 'contact_request_id');
    }

    public function employmentRecord(): HasOne
    {
        return $this->hasOne(JobEmploymentRecord::class, 'contact_transaction_id');
    }
}
