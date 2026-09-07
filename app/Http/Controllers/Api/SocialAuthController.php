<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google/Facebook OAuth provider
     */
    public function redirect(Request $request, string $provider)
    {
        $request->validate([
            'role' => 'required|in:candidate,employer',
        ]);

        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['status' => false, 'message' => 'Invalid provider'], 400);
        }

        // Check if provider is enabled in settings
        $enabled = \App\Models\Setting::where('key', "social_auth_{$provider}_enabled")->first();
        if ($enabled && !$enabled->value) {
            return response()->json(['status' => false, 'message' => ucfirst($provider) . ' login is disabled'], 400);
        }

        // Store role in session for callback
        session(['social_auth_role' => $request->role]);

        return Socialite::driver($provider)
            ->scopes($provider === 'google' ? ['email', 'profile'] : ['email'])
            ->redirect();
    }

    /**
     * Handle OAuth callback from Google/Facebook
     */
    public function callback(Request $request, string $provider)
    {
        try {
            if (!in_array($provider, ['google', 'facebook'])) {
                return response()->json(['status' => false, 'message' => 'Invalid provider'], 400);
            }

            $socialUser = Socialite::driver($provider)->user();
            $role = session('social_auth_role', 'candidate');

            // Find or create user
            $user = null;
            $isNewUser = false;

            // Check by provider ID first
            if ($provider === 'google') {
                $user = User::where('google_id', $socialUser->getId())->first();
            } elseif ($provider === 'facebook') {
                $user = User::where('facebook_id', $socialUser->getId())->first();
            }

            // If not found by provider ID, check by email
            if (!$user && $socialUser->getEmail()) {
                $user = User::where('email', $socialUser->getEmail())->first();

                // Link existing account to social provider
                if ($user) {
                    if ($provider === 'google') {
                        $user->update(['google_id' => $socialUser->getId()]);
                    } else {
                        $user->update(['facebook_id' => $socialUser->getId()]);
                    }
                }
            }

            // Create new user if not found
            if (!$user) {
                $isNewUser = true;
                $name = $socialUser->getName() ?: $socialUser->getNickname() ?: 'User';
                $email = $socialUser->getEmail();

                // Require email from social provider - redirect to complete registration if not available
                if (!$email) {
                    $pendingToken = Str::random(60);
                    Cache::put("social_pending:{$pendingToken}", [
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'name' => $name,
                        'avatar' => $socialUser->getAvatar(),
                    ], now()->addMinutes(30));

                    return redirect(config('app.frontend_url', 'https://ejobs.bd') . "/auth/complete-registration?token={$pendingToken}&provider={$provider}");
                }

                // Generate unique username
                $username = Str::slug($name) . '-' . Str::random(5);

                $userData = [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => null, // No password for social auth users
                    'avatar' => $socialUser->getAvatar(),
                    'provider' => $provider,
                ];

                if ($provider === 'google') {
                    $userData['google_id'] = $socialUser->getId();
                } else {
                    $userData['facebook_id'] = $socialUser->getId();
                }

                $user = User::create($userData);
                $user->assignRole($role);

                // Create profile
                UserProfile::create(['user_id' => $user->id]);

                Log::info("New social auth user created: {$user->email} via {$provider}");
            }

            // Generate Sanctum token
            $token = $user->createToken('social_auth_token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&role={$user->getRoleNames()->first()}&provider={$provider}";

            return redirect($redirectUrl);

        } catch (\Throwable $e) {
            Log::error("Social auth callback error: " . $e->getMessage());
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            return redirect("{$frontendUrl}/login?error=social_auth_failed");
        }
    }

    /**
     * API endpoint to check social auth settings status
     */
    public function getSettings()
    {
        $settings = [
            'google_enabled' => $this->getSetting('social_auth_google_enabled', 'false'),
            'facebook_enabled' => $this->getSetting('social_auth_facebook_enabled', 'false'),
        ];

        return response()->json(['status' => true, 'data' => $settings]);
    }

    private function getSetting(string $key, string $default = ''): string
    {
        $setting = \App\Models\Setting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
