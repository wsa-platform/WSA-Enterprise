<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeEmbedding extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'organization_id',
        'embedding',
        'embedding_model',
        'embedding_dimensions',
        'content_hash',
        'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'embedding_dimensions' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }
}
