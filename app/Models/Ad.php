<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Ad extends Model
{
    protected $fillable = [
        'title', 'description', 'media_type', 'media_path', 'target_url',
        'cta_text', 'start_date', 'end_date', 'status', 'priority',
        'approval_status', 'monetization_model', 'rate', 'max_clicks',
        'max_impressions', 'total_clicks', 'total_impressions', 'role_target',
        'language_target', 'device_target', 'subscription_target', 'location_target'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'rate' => 'float',
        'priority' => 'integer',
        'max_clicks' => 'integer',
        'max_impressions' => 'integer',
        'total_clicks' => 'integer',
        'total_impressions' => 'integer',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(AdPlacement::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AdLog::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('approval_status', 'approved')
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('max_impressions')
                  ->orWhereColumn('total_impressions', '<', 'max_impressions');
            })
            ->where(function ($q) {
                $q->whereNull('max_clicks')
                  ->orWhereColumn('total_clicks', '<', 'max_clicks');
            });
    }

    public function scopeTargetedTo(Builder $query, string $role, string $language, string $device, ?string $subscription = null): Builder
    {
        return $query->where(function ($q) use ($role) {
            $q->where('role_target', 'all')
              ->orWhere('role_target', $role);
        })->where(function ($q) use ($language) {
            $q->where('language_target', 'all')
              ->orWhere('language_target', $language);
        })->where(function ($q) use ($device) {
            $q->where('device_target', 'all')
              ->orWhere('device_target', $device);
        })->where(function ($q) use ($subscription) {
            if ($subscription) {
                $q->whereNull('subscription_target')
                  ->orWhere('subscription_target', $subscription);
            } else {
                $q->whereNull('subscription_target');
            }
        });
    }
}
