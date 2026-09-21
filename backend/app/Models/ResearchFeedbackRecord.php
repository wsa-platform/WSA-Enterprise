<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * R7 Feedback Dataset row — positive polarity only.
 */
class ResearchFeedbackRecord extends Model
{
    protected $table = 'research_feedback_dataset';

    protected $fillable = [
        'organization_id',
        'polarity',
        'research_source',
        'question_language',
        'answer_language',
        'ui_locale',
        'question',
        'library_item_id',
        'research_fingerprint',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
