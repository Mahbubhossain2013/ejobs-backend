<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModerationLog extends Model
{
    protected $fillable = [
        'user_id',
        'message_text',
        'detected_violations',
        'confidence_score',
        'provider',
        'action_taken',
    ];

    protected $casts = [
        'detected_violations' => 'array',
        'confidence_score' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
