<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'provider',
        'model_code',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'duration_ms',
        'status',
        'error_message',
        'was_fallback',
        'is_cached',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'duration_ms' => 'integer',
        'was_fallback' => 'boolean',
        'is_cached' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
