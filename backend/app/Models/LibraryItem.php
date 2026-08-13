<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LibraryItem extends Model
{
    use BelongsToOwner;

    protected $fillable = [
        'organization_id', 'owner_user_id', 'category_id', 'crop_type_id', 'slug', 'title', 'title_ar',
        'summary', 'summary_ar', 'content', 'content_ar', 'item_type', 'author', 'source',
        'locale', 'publication_status', 'published_at', 'file_disk', 'file_path', 'metadata',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'metadata' => 'array'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'category_id');
    }

    public function cropType(): BelongsTo
    {
        return $this->belongsTo(CropType::class, 'crop_type_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(LibraryTag::class, 'library_item_tag');
    }
}
