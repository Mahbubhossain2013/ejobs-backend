<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ad\AdServingService;
use App\Services\Ad\AdFraudDetectionService;
use App\Models\Promotion;
use Illuminate\Http\Request;

class AdController extends Controller
{
    /**
     * Serve targeted advertisement matching active slots parameters
     */
    public function serve(Request $request)
    {
        $request->validate([
            'slot' => 'required|string'
        ]);

        $slot = $request->slot;

        // 1. Detect target language
        $language = $request->get('lang') ?? 'en';
        if (str_contains(strtolower($request->header('Accept-Language')), 'bn') || str_contains(strtolower($request->header('lang')), 'bn')) {
            $language = 'bn';
        }

        // 2. Detect target device type
        $userAgent = $request->header('User-Agent');
        $device = 'desktop';
        if ($userAgent && (str_contains(strtolower($userAgent), 'mobile') || str_contains(strtolower($userAgent), 'android') || str_contains(strtolower($userAgent), 'iphone'))) {
            $device = 'mobile';
        }

        // 3. Detect user role and active subscription plan
        $user = auth('sanctum')->user();
        $role = 'guest';
        $subscription = null;

        if ($user) {
            $role = $user->role ?? 'candidate';
            
            // Map dynamic subscription tiers if available
            if (isset($user->subscription_tier)) {
                $subscription = $user->subscription_tier;
            } elseif (isset($user->plan_id)) {
                $subscription = $user->plan_id;
            }
        }

        // Fetch targeted ad
        $ad = AdServingService::getAdForSlot($slot, $role, $language, $device, $subscription);

        if (!$ad) {
            return response()->json([
                'status' => false,
                'message' => 'No active targeted advertisement served for this slot.'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'media_type' => $ad->media_type,
                'media_path' => $ad->media_path,
                'target_url' => $ad->target_url,
                'cta_text' => $ad->cta_text,
                'monetization_model' => $ad->monetization_model,
            ]
        ]);
    }

    /**
     * Telemetry callback recording a CPM impression
     */
    public function logImpression(Request $request, $id)
    {
        $status = AdServingService::logEvent(
            (int)$id, 
            'impression', 
            $request->ip(), 
            $request->header('User-Agent'), 
            auth('sanctum')->id()
        );

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Impression logged successfully.' : 'Failed to track impression event.'
        ]);
    }

    /**
     * Telemetry callback recording a CPC click with fraud detection
     */
    public function logClick(Request $request, $id)
    {
        $ip = $request->ip();
        $userAgent = $request->header('User-Agent');

        // Run fraud detection against active promotion campaigns
        $promo = Promotion::where('id', $id)->whereIn('status', ['active', 'pending_review'])->first();
        if ($promo) {
            $isFraudulent = AdFraudDetectionService::detectClickFraud($promo, $ip, $userAgent);
            if ($isFraudulent) {
                return response()->json([
                    'status' => false,
                    'message' => 'Click flagged as suspicious and not recorded.'
                ]);
            }
        }

        $status = AdServingService::logEvent(
            (int)$id, 
            'click', 
            $ip, 
            $userAgent, 
            auth('sanctum')->id()
        );

        return response()->json([
            'status' => $status,
            'message' => $status ? 'Click logged successfully.' : 'Failed to track click event.'
        ]);
    }
}
