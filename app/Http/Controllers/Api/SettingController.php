<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function getThemeSettings()
    {
        try {
            $data = Cache::remember('theme_settings_v1', 600, function () {
                $safeKeys = [
                    'site_name',
                    'homepage_url',
                    'timezone',
                    'company_address',
                    'city',
                    'zip_code',
                    'site_logo',
                    'site_logo_dark',
                    'site_favicon',
                    'site_favicon_dark',
                    'border_radius',
                    'support_phone',
                    'support_email',
                    'support_website',
                    'facebook_page',
                    'facebook_messenger',
                    'whatsapp_number',
                    'telegram_channel',
                    'youtube_channel',
                    'meta_description',
                    'seo_keywords',
                    'og_image',
                    'twitter_image',
                    'primary_color',
                    'button_bg',
                    'nav_bg',
                    'nav_bg_dark',
                    'nav_text_color',
                    'nav_text_hover',
                    'english_font',
                    'bangla_font',
                    'global_font_size',
                    'font_size',
                    'phone_font_size',
                    'tablet_font_size',
                    'default_currency',
                    'currency_symbol',
                    'default_language',
                    'gtm_id',
                    'facebook_pixel_id',
                    'homepage_trending_searches',
                    'homepage_trending_searches_bn',
                    'homepage_features_grid',
                    'homepage_offer_banner',
                    'homepage_quick_links',
                    'homepage_recruitment_callout',
                    'homepage_notices'
                ];

                $settings = Setting::whereIn('key', $safeKeys)->get();
                $out = [];
                foreach ($settings as $setting) {
                    $decoded = json_decode($setting->value, true);
                    $out[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
                }
                return $out;
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error("Theme Settings API Error: " . $e->getMessage());
            // Return empty safe data instead of crashing
            return response()->json(['status' => true, 'data' => []]);
        }
    }

    public function getNotices()
    {
        try {
            $data = Cache::remember('homepage_notices', 900, function () {
                $setting = \App\Models\Setting::where('key', 'homepage_notices')->first();
                if ($setting && !empty($setting->value)) {
                    $notices = json_decode($setting->value, true);
                    if (is_array($notices)) {
                        usort($notices, function ($a, $b) {
                            return strcmp($b['published_at'] ?? '', $a['published_at'] ?? '');
                        });
                        return $notices;
                    }
                }

                return \App\Models\Notice::orderBy('published_at', 'desc')->take(10)->get()->toArray();
            });

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error("Get Notices API Error: " . $e->getMessage());
            return response()->json(['status' => false, 'data' => []]);
        }
    }
}
