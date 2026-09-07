<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdPlacement extends Model
{
    protected $fillable = ['ad_id', 'slot', 'is_enabled'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }
}
