<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiConfig extends Model
{
    protected $fillable = [
        'provider_name',
        'provider_key',
        'api_key',
        'api_url',
        'model_code',
        'timeout',
        'purpose',
        'system_instruction',
        'priority',
        'cost_rating',
        'speed_rating',
        'accuracy_rating',
        'is_active',
        'is_hidden',
        'failure_count',
        'last_failed_at',
    ];

    protected $casts = [
        'priority' => 'integer',
        'cost_rating' => 'integer',
        'speed_rating' => 'integer',
        'accuracy_rating' => 'integer',
        'is_active' => 'boolean',
        'is_hidden' => 'boolean',
        'failure_count' => 'integer',
        'last_failed_at' => 'datetime',
    ];

    public static function getActive()
    {
        return self::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->first();
    }

    public static function getByPurpose(string $purpose)
    {
        return self::where('purpose', $purpose)
            ->where('is_active', true)
            ->first() ?? self::getActive();
    }

    /**
     * Get all active providers ordered by priority for failover pipelines
     */
    public static function getFailoverChain()
    {
        return self::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();
    }
}
