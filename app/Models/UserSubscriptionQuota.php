<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscriptionQuota extends Model
{
    protected $fillable = [
        'user_id',
        'user_subscription_id',
        'feature_key',
        'used',
        'max_limit',
        'reset_at',
    ];

    protected $casts = [
        'used' => 'integer',
        'max_limit' => 'integer',
        'reset_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }
}
