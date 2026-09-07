<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'name',
        'badge_key',
        'description',
        'icon',
        'color',
        'priority',
        'is_active',
        'role',
        'icon_type',
        'icon_path',
        'rules',
        'is_automatic',
        'rarity',
        'is_hidden',
        'badge_type',
    ];

    protected $casts = [
        'priority' => 'integer',
        'is_active' => 'boolean',
        'rules' => 'array',
        'is_automatic' => 'boolean',
        'is_hidden' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'badge_user')
            ->withPivot('assigned_by', 'expires_at', 'is_visible', 'earned_at')
            ->withTimestamps();
    }
}
