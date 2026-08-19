<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BeeKnowledgeTopic extends Model
{
    protected $fillable = [
        'slug',
        'category',
        'title_key',
        'summary_key',
        'body',
        'tags',
        'metadata',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
