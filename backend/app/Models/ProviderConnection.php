<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderConnection extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'provider_key',
        'enabled',
        'last_test_at',
        'last_test_status',
        'last_test_error',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'last_test_at' => 'datetime',
        ];
    }

    public static function forKey(string $key): self
    {
        return static::firstOrCreate(
            ['provider_key' => $key],
            ['enabled' => true],
        );
    }
}
