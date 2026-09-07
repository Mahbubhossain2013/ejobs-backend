<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSecurityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'activity_type',
        'risk_score',
        'vpn_detected',
        'moderation_details',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'vpn_detected' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
