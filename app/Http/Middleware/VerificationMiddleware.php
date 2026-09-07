<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action)
    {
        // 1. Check if the verification system is globally enabled
        $globalEnabled = \App\Models\Setting::where('key', 'verification_enabled')->value('value') ?? '0';
        if ($globalEnabled !== '1' && $globalEnabled !== 'true') {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // 2. Admins are exempt from restriction policies
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $next($request);
        }

        // 3. Resolve the configuration toggle corresponding to the action
        $settingKey = match($action) {
            'apply' => 'verification_require_to_apply',
            'post' => 'verification_require_to_post',
            'wallet' => 'verification_restrict_wallet',
            'escrow' => 'verification_restrict_remote_escrow',
            default => null,
        };

        if ($settingKey) {
            $restricted = \App\Models\Setting::where('key', $settingKey)->value('value') ?? '0';
            if ($restricted === '1' || $restricted === 'true') {
                // If restriction is enabled, ensure the user has the 'verified' badge
                if (!$user->hasBadge('verified')) {
                    return response()->json([
                        'status' => false,
                        'verification_required' => true,
                        'message' => "Verification Required: Please fully verify your identity (NID/Employer documents) in the Verification Center to unlock this feature."
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
