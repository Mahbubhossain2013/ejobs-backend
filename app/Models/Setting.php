<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable =['key', 'value'];

    const CACHE_KEY = 'platform_settings';
    const CACHE_TTL = 300;

    public static function getSettings(): \Illuminate\Support\Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::all()->pluck('value', 'key');
        });
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}