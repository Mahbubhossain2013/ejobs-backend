<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerRoadmap extends Model
{
    protected $fillable = [
        'user_id',
        'data',
        'user_prompt',
        'current_level',
        'target_role',
    ];

    protected $casts = [
        'data' => 'array',
        'current_level' => 'array',
        'target_role' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
