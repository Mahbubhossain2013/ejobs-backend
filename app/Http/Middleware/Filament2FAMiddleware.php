<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Filament2FAMiddleware
{
    /**
     * Intercept and audit Filament Admin requests. Redirects to challenge if 2FA is required.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Use standard web guard as Filament integrates natively with it
        if (Auth::check()) {
            $user = Auth::user();
            
            // 1. Determine if challenge is required (Optional 2FA: only if enabled by user)
            $needsChallenge = $user->has2faEnabled();

            if ($needsChallenge && !session()->get('filament.2fa.verified')) {
                // Allow request to bypass if they are hitting the challenge routes or Livewire updates
                $path = $request->path();
                if (str_contains($path, 'two-factor-challenge') || str_contains($path, 'logout') || str_contains($path, 'livewire')) {
                    return $next($request);
                }

                return redirect()->route('filament.admin.two-factor-challenge');
            }
        }

        return $next($request);
    }
}
