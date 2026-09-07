<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionFeature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        // Bypass for administrators
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return $next($request);
        }

        // Validate plan feature availability
        if (!$user->hasFeature($featureKey)) {
            return response()->json([
                'status' => false,
                'message' => "Access denied. Your current plan does not support this feature: " . ucwords(str_replace('_', ' ', $featureKey)) . ". Please upgrade your subscription plan."
            ], 403);
        }

        return $next($request);
    }
}
