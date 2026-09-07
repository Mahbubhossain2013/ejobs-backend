<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTrustScore extends Model
{
    protected $table = 'user_trust_scores';

    protected $fillable = [
        'user_id',
        'trust_score',
        'risk_level',
        'violation_count',
    ];

    protected $casts = [
        'trust_score' => 'integer',
        'violation_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
