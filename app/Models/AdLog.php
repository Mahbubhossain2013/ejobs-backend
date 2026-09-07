<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdLog extends Model
{
    protected $fillable = [
        'ad_id', 'event_type', 'ip_address', 'user_id',
        'user_agent', 'device', 'revenue'
    ];

    protected $casts = [
        'revenue' => 'float',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
