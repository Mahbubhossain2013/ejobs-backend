<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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
        $role = $request->input('role', 'candidate');
        if (!in_array($role, ['candidate', 'employer'])) {
            $role = 'candidate';
        }

        if (!in_array($provider, ['google', 'facebook'])) {
            return response()->json(['status' => false, 'message' => 'Invalid provider'], 400);
        }

        // Check if provider is enabled in settings
        $enabled = \App\Models\Setting::where('key', "social_auth_{$provider}_enabled")->first();
        if ($enabled && !$enabled->value) {
            return response()->json(['status' => false, 'message' => ucfirst($provider) . ' login is disabled'], 400);
        }

        // Store role in state parameter so stateless OAuth preserves it without session cookies
        $stateData = base64_encode(json_encode([
            'role' => $role,
            'nonce' => Str::random(16),
        ]));

        return Socialite::driver($provider)
            ->stateless()
            ->scopes($provider === 'google' ? ['email', 'profile'] : ['email'])
            ->with(['state' => $stateData])
            ->redirect();
    }

    /**
     * Handle OAuth callback from Google/Facebook
     */
    public function callback(Request $request, string $provider)
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.frontend_url', 'https://ejobs.bd')), '/');

        try {
            if (!in_array($provider, ['google', 'facebook'])) {
                return redirect("{$frontendUrl}/login?error=invalid_provider");
            }

            // Use stateless() to eliminate cross-domain session cookie and CSRF state failures
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Decode role from OAuth state parameter
            $role = 'candidate';
            $rawState = $request->input('state');
            if ($rawState) {
                $decoded = json_decode(base64_decode($rawState), true);
                if (!empty($decoded['role']) && in_array($decoded['role'], ['candidate', 'employer'])) {
                    $role = $decoded['role'];
                }
            } elseif (session()->has('social_auth_role')) {
                $role = session('social_auth_role');
            }

            // Find or create user
            $user = null;

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
                $name = $socialUser->getName() ?: $socialUser->getNickname() ?: 'User';
                $email = $socialUser->getEmail();

                // Require email from social provider
                if (!$email) {
                    $pendingToken = Str::random(60);
                    Cache::put("social_pending:{$pendingToken}", [
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'name' => $name,
                        'avatar' => $socialUser->getAvatar(),
                    ], now()->addMinutes(30));

                    return redirect("{$frontendUrl}/auth/complete-registration?token={$pendingToken}&provider={$provider}");
                }

                // Generate unique username
                $baseSlug = Str::slug($name);
                $username = ($baseSlug ?: 'user') . '-' . Str::random(5);
                while (User::where('username', $username)->exists()) {
                    $username = ($baseSlug ?: 'user') . '-' . Str::random(5);
                }

                $userData = [
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)), // Column is NOT NULL
                    'avatar' => $socialUser->getAvatar(),
                    'provider' => $provider,
                    'email_verified_at' => now(),
                ];

                if ($provider === 'google') {
                    $userData['google_id'] = $socialUser->getId();
                } else {
                    $userData['facebook_id'] = $socialUser->getId();
                }

                $user = User::create($userData);

                try {
                    $user->assignRole($role);
                } catch (\Throwable $re) {
                    Log::warning("Assign role {$role} to user {$user->id} failed: " . $re->getMessage());
                }

                // Create profile
                if ($role === 'candidate') {
                    UserProfile::firstOrCreate(['user_id' => $user->id]);
                } else {
                    UserProfile::firstOrCreate(['user_id' => $user->id]);
                    \App\Models\Company::firstOrCreate([
                        'user_id' => $user->id,
                    ], [
                        'name' => $name,
                        'slug' => Str::slug($name) . '-' . Str::random(6),
                        'is_verified' => false,
                    ]);
                }

                Log::info("New social auth user created: {$user->email} via {$provider}");
            }

            // Generate Sanctum token
            $token = $user->createToken('social_auth_token')->plainTextToken;

            // Determine user role
            $userRole = null;
            try {
                $userRole = $user->getRoleNames()->first();
            } catch (\Throwable $ignored) {}
            if (!$userRole) {
                $userRole = $user->role ?: $role;
            }

            // Redirect to frontend with token
            $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&role={$userRole}&provider={$provider}";

            return redirect($redirectUrl);

        } catch (\Throwable $e) {
            Log::error("Social auth callback error: " . get_class($e) . ': ' . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
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

