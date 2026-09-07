<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBehaviorLog extends Model
{
    // The logs don't use default updated_at since they are insert-only telemetry logs
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'activity_type',
        'target_id',
        'meta_data',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'target_id' => 'integer',
        'meta_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
