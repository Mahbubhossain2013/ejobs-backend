<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemVersion extends Model
{
    protected $fillable = ['version', 'build_number', 'release_notes', 'is_current', 'installed_at'];

    protected $casts = [
        'is_current' => 'boolean',
        'installed_at' => 'datetime',
    ];

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public static function getCurrentVersion(): string
    {
        $current = static::current();

        return $current?->version ?? '1.0.0';
    }
}
