<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSecurityLog;
use App\Services\Security\TwoFactorService;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class TwoFactorApiController extends Controller
{
    /**
     * Initialize secret key generation and compilation of scan QR code details
     */
    public function setup(Request $request)
    {
        $user = Auth::user();

        if ($user->two_factor_enabled && $user->two_factor_confirmed_at !== null) {
            return response()->json(['status' => false, 'message' => 'Two-Factor Authentication is already enabled on your account.'], 400);
        }

        // Check if there is an unconfirmed secret already generated
        $secret = $user->two_factor_secret;
        if (!$secret) {
            $secret = TwoFactorService::generateSecretKey();
            $user->update(['two_factor_secret' => $secret]);
        }

        $qrUrl = TwoFactorService::getQrCodeUrl('JobPortalApp', $user->email, $secret);
        $otpauthUri = TwoFactorService::getOtpauthUri('JobPortalApp', $user->email, $secret);

        return response()->json([
            'status' => true,
            'secret' => $secret,
            'qr_code_url' => $qrUrl,
            'otpauth_uri' => $otpauthUri
        ]);
    }

    /**
     * Validate first-time TOTP OTP key to confirm and activate 2FA onboarding
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $code = trim($request->code);

        if (!$user->two_factor_secret) {
            return response()->json(['status' => false, 'message' => 'No 2FA configuration key exists. Please call setup first.'], 400);
        }

        // Validate code
        if (TwoFactorService::verifyCode($user->two_factor_secret, $code)) {
            $user->two_factor_confirmed_at = now();
            $user->two_factor_enabled = true;
            $user->save();

            // Generate security recovery codes list
            $recoveryCodes = $user->generateRecoveryCodes();

            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $fingerprint = hash('sha256', $userAgent . $ip);
            
            SecurityAuditService::logEvent(
                $user->id, $ip, $userAgent, $fingerprint, '2fa_enabled', 
                0, false, 'Candidate/Employer enabled Two-Factor Authentication.'
            );

            return response()->json([
                'status' => true,
                'message' => 'Two-Factor Authentication activated successfully!',
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Invalid OTP code. Scan the QR code again.'], 422);
    }

    /**
     * Disable Two-Factor Authentication (requires entering OTP to confirm)
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::user();
        $code = trim($request->code);
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $fingerprint = hash('sha256', $userAgent . $ip);

        // 1. Verify standard OTP
        $verified = TwoFactorService::verifyCode($user->two_factor_secret, $code);

        // 2. Fallback: Verify recovery codes
        if (!$verified) {
            $verified = TwoFactorService::verifyRecoveryCode($user, $code);
        }

        if ($verified) {
            $user->update([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_enabled' => false,
                'two_factor_method' => null,
                'trusted_devices' => null
            ]);

            SecurityAuditService::logEvent(
                $user->id, $ip, $userAgent, $fingerprint, '2fa_disabled', 
                10, false, 'Two-Factor Authentication deactivated by user.'
            );

            return response()->json([
                'status' => true,
                'message' => 'Two-Factor Authentication has been successfully deactivated.'
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Invalid verification code.'], 422);
    }

    /**
     * Fetch active login devices & sessions lists
     */
    public function getSessions(Request $request)
    {
        $user = Auth::user();
        $sessions = [];

        if ($user->profile) {
            $sessions = $user->profile->active_sessions ?? [];
        } elseif ($user->company) {
            $sessions = $user->company->active_sessions ?? [];
        }

        // Format sessions as array
        $sessionsList = [];
        $currentFingerprint = md5($request->userAgent() . $request->ip());

        foreach ($sessions as $key => $session) {
            $sessionsList[] = [
                'fingerprint' => $key,
                'ip_address' => $session['ip'] ?? 'N/A',
                'user_agent' => $session['user_agent'] ?? 'N/A',
                'country' => $session['country'] ?? 'Bangladesh',
                'last_active' => isset($session['last_active']) ? \Carbon\Carbon::parse($session['last_active'])->diffForHumans() : 'N/A',
                'is_current' => $key === $currentFingerprint
            ];
        }

        return response()->json([
            'status' => true,
            'sessions' => $sessionsList
        ]);
    }

    /**
     * Revoke a specific active browser device session by fingerprint key
     */
    public function logoutSession(Request $request)
    {
        $request->validate([
            'fingerprint' => 'required|string'
        ]);

        $user = Auth::user();
        $fingerprint = $request->fingerprint;

        if ($user->profile) {
            $sessions = $user->profile->active_sessions ?? [];
            if (isset($sessions[$fingerprint])) {
                unset($sessions[$fingerprint]);
                $user->profile->update(['active_sessions' => $sessions]);
            }
        }

        if ($user->company) {
            $sessions = $user->company->active_sessions ?? [];
            if (isset($sessions[$fingerprint])) {
                unset($sessions[$fingerprint]);
                $user->company->update(['active_sessions' => $sessions]);
            }
        }

        // Invalidate tokens matching UA / IP (in a real app, delete that target Sanctum token)
        // For simplicity, log security audit trail revoke
        SecurityAuditService::logEvent(
            $user->id, $request->ip(), $request->userAgent(), hash('sha256', $request->userAgent().$request->ip()),
            'session_revoked', 5, false, "Device session '{$fingerprint}' revoked manually."
        );

        return response()->json([
            'status' => true,
            'message' => 'Device session successfully revoked.'
        ]);
    }

    /**
     * Invalidate all sessions except the current browser
     */
    public function logoutAllSessions(Request $request)
    {
        $user = Auth::user();
        $currentFingerprint = md5($request->userAgent() . $request->ip());

        // Invalidate other Sanctum tokens
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        // Clear active session maps
        if ($user->profile) {
            $sessions = $user->profile->active_sessions ?? [];
            $currentSession = $sessions[$currentFingerprint] ?? null;
            $user->profile->update([
                'active_sessions' => $currentSession ? [$currentFingerprint => $currentSession] : null
            ]);
        }

        if ($user->company) {
            $sessions = $user->company->active_sessions ?? [];
            $currentSession = $sessions[$currentFingerprint] ?? null;
            $user->company->update([
                'active_sessions' => $currentSession ? [$currentFingerprint => $currentSession] : null
            ]);
        }

        SecurityAuditService::logEvent(
            $user->id, $request->ip(), $request->userAgent(), hash('sha256', $request->userAgent().$request->ip()),
            'all_sessions_revoked', 5, false, 'All alternative device sessions revoked.'
        );

        return response()->json([
            'status' => true,
            'message' => 'All other device sessions successfully revoked.'
        ]);
    }

    /**
     * Retrieve security activity ledger records
     */
    public function getHistoryLogs(Request $request)
    {
        $logs = UserSecurityLog::where('user_id', Auth::id())
            ->latest('id')
            ->take(30)
            ->get();

        return response()->json([
            'status' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Change user password securely and reset all other logins
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['status' => false, 'message' => 'The provided current password is incorrect.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Revoke all other login sessions instantly
        SecurityAuditService::invalidateSessionsOnPasswordReset($user);

        // Re-generate current token so the user stays logged in on their current browser
        $newToken = $user->createToken('auth_token')->plainTextToken;

        SecurityAuditService::logEvent(
            $user->id, $request->ip(), $request->userAgent(), hash('sha256', $request->userAgent().$request->ip()),
            'password_changed', 0, false, 'User password modified successfully. Active sessions flushed.'
        );

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully! All other sessions have been logged out.',
            'token' => $newToken
        ]);
    }

    /**
     * Get current 2FA status for the authenticated user
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'status' => true,
            'enabled' => $user->two_factor_enabled && $user->two_factor_confirmed_at !== null,
            'method' => $user->two_factor_method ?? 'totp',
            'has_phone' => !empty($user->profile->phone),
            'email' => $user->email,
        ]);
    }

    /**
     * Send OTP for 2FA setup via SMS or Email
     */
    public function sendOtpSetup(Request $request)
    {
        $request->validate([
            'channel' => 'required|string|in:sms,email',
        ]);

        $user = Auth::user();

        if ($user->two_factor_enabled && $user->two_factor_confirmed_at !== null) {
            return response()->json(['status' => false, 'message' => '2FA is already enabled. Disable it first.'], 400);
        }

        if ($request->channel === 'sms' && empty($user->profile->phone)) {
            return response()->json(['status' => false, 'message' => 'Add a phone number to your profile first.'], 422);
        }

        $result = \App\Services\Security\TwoFactorOtpService::sendOtp($user, $request->channel);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * Verify OTP code and activate 2FA for SMS or Email method
     */
    public function confirmOtpSetup(Request $request)
    {
        $request->validate([
            'channel' => 'required|string|in:sms,email',
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $verified = \App\Services\Security\TwoFactorOtpService::verifyOtp(
            $user, $request->channel, trim($request->code)
        );

        if (!$verified) {
            return response()->json(['status' => false, 'message' => 'Invalid or expired OTP code.'], 422);
        }

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_method' => $request->channel,
        ]);

        $recoveryCodes = $user->generateRecoveryCodes();

        return response()->json([
            'status' => true,
            'message' => ucfirst($request->channel) . ' 2FA activated successfully!',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Send OTP during login (for SMS/Email 2FA)
     */
    public function sendLoginOtp(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
        ]);

        $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($request->temp_token);
        [$userId, $expiry] = explode('|', $decrypted);

        if (now()->timestamp > $expiry) {
            return response()->json(['status' => false, 'message' => 'Login session expired. Please log in again.'], 422);
        }

        $user = User::findOrFail($userId);

        if (!$user || !$user->two_factor_enabled) {
            return response()->json(['status' => false, 'message' => 'Invalid request.'], 400);
        }

        $channel = $user->two_factor_method ?? 'sms';
        $result = \App\Services\Security\TwoFactorOtpService::sendOtp($user, $channel);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    /**
     * Verify login OTP (for SMS/Email 2FA)
     */
    public function verifyLoginOtp(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($request->temp_token);
        [$userId, $expiry] = explode('|', $decrypted);

        if (now()->timestamp > $expiry) {
            return response()->json(['status' => false, 'message' => 'Login session expired. Please log in again.'], 422);
        }

        $user = User::findOrFail($userId);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Invalid request.'], 400);
        }

        $channel = $user->two_factor_method ?? 'sms';
        $verified = \App\Services\Security\TwoFactorOtpService::verifyOtp(
            $user, $channel, trim($request->code)
        );

        if (!$verified) {
            return response()->json(['status' => false, 'message' => 'Invalid OTP code.'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'token' => $token,
            'user' => $user->load('profile', 'company'),
            'role' => $user->role ?? $user->getRoleNames()->first(),
        ]);
    }
}
