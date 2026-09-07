<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class StaticPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title_en',
        'title_bn',
        'content_en',
        'content_bn',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    const CACHE_PREFIX = 'static_page_';
    const CACHE_TTL = 3600;

    public static function getBySlug(string $slug): ?self
    {
        $cacheKey = self::CACHE_PREFIX . $slug;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return static::where('slug', $slug)->where('is_active', true)->first();
        });
    }

    public static function flushCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget(self::CACHE_PREFIX . $slug);
        } else {
            foreach (['privacy', 'terms', 'contact', 'about', 'faq'] as $s) {
                Cache::forget(self::CACHE_PREFIX . $s);
            }
        }
    }
}
