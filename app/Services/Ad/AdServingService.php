<?php

namespace App\Services\Ad;

use App\Models\Ad;
use App\Models\AdPlacement;
use App\Models\AdLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdServingService
{
    /**
     * Retrieve a highly targeted ad for a specific slot, language, device, and role
     */
    public static function getAdForSlot(string $slot, string $role, string $language, string $device, ?string $subscription = null): ?Ad
    {
        $cacheKey = "ad_slot_{$slot}_{$role}_{$language}_{$device}_" . ($subscription ?? 'none');

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($slot, $role, $language, $device, $subscription) {
            return Ad::active()
                ->targetedTo($role, $language, $device, $subscription)
                ->whereHas('placements', function ($query) use ($slot) {
                    $query->where('slot', $slot)->where('is_enabled', true);
                })
                ->orderByDesc('priority')
                ->orderByDesc('created_at')
                ->first();
        });
    }

    /**
     * Invalidate cached ads for specific slots instead of flushing all caches
     */
    public static function invalidateAllCaches(): void
    {
        try {
            $slots = ['homepage_hero', 'sidebar_candidate', 'sidebar_employer', 'inline_jobs', 'feed', 'widget', 'popup', 'sticky_banner'];
            $roles = ['guest', 'candidate', 'employer'];
            $languages = ['en', 'bn'];
            $devices = ['desktop', 'mobile'];

            foreach ($slots as $slot) {
                foreach ($roles as $role) {
                    foreach ($languages as $lang) {
                        foreach ($devices as $device) {
                            $key = "ad_slot_{$slot}_{$role}_{$lang}_{$device}_none";
                            Cache::forget($key);
                        }
                    }
                }
            }
            Log::info("Ads Caching System: Targeted ad slot caches invalidated.");
        } catch (\Exception $e) {
            Log::error("Ads Caching System: Failed to invalidate caches: " . $e->getMessage());
        }
    }

    /**
     * Record CPC or CPM log records dynamically
     */
    public static function logEvent(int $adId, string $eventType, ?string $ip = null, ?string $userAgent = null, ?int $userId = null): bool
    {
        try {
            $ad = Ad::find($adId);
            if (!$ad) return false;

            $device = 'desktop';
            if ($userAgent && (str_contains(strtolower($userAgent), 'mobile') || str_contains(strtolower($userAgent), 'android') || str_contains(strtolower($userAgent), 'iphone'))) {
                $device = 'mobile';
            }

            // Calculate revenue charge based on Monetization rates
            $revenue = 0.0000;
            if ($eventType === 'click' && $ad->monetization_model === 'cpc') {
                $revenue = $ad->rate;
            } elseif ($eventType === 'impression' && $ad->monetization_model === 'cpm') {
                $revenue = $ad->rate / 1000.00;
            }

            // Increment transactional counters
            if ($eventType === 'click') {
                $ad->increment('total_clicks');
            } else {
                $ad->increment('total_impressions');
            }

            AdLog::create([
                'ad_id' => $adId,
                'event_type' => $eventType,
                'ip_address' => $ip,
                'user_id' => $userId,
                'user_agent' => $userAgent,
                'device' => $device,
                'revenue' => $revenue
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Ad Telemetry logging failed: " . $e->getMessage());
            return false;
        }
    }
}
