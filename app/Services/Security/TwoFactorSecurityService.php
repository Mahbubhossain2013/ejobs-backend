<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\TrustedDevice;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorSecurityService
{
    /**
     * Generate a secure random 16-character Base32 secret string
     */
    public function generateSecretKey(): string
    {
        return TwoFactorService::generateSecretKey();
    }

    /**
     * Get the TOTP QR Code URL using api.qrserver.com
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        return TwoFactorService::getQrCodeUrl('JobPortalAdmin', $user->email, $secret);
    }

    /**
     * Verify a 6-digit TOTP code with strict rate limiting
     * Max 5 attempts per 10 minutes
     */
    public function verifyOtp(User $user, string $secret, string $code, Request $request): array
    {
        $rateLimitKey = '2fa-attempts:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);
            
            $this->logSecurityEvent(
                $user, 'failed_otp', 'fail', 
                "Verification blocked due to rate limiting. Retrying in {$minutes} minutes.", $request
            );

            return [
                'status' => false,
                'message' => "Too many verification attempts. Please try again in {$minutes} minutes."
            ];
        }

        $verified = TwoFactorService::verifyCode($secret, $code);

        if ($verified) {
            RateLimiter::clear($rateLimitKey);
            
            $this->logSecurityEvent(
                $user, 'successful_otp', 'success', 
                "MFA OTP successfully verified.", $request
            );

            return ['status' => true, 'message' => 'OTP verified successfully.'];
        }

        RateLimiter::hit($rateLimitKey, 600); // 10 minutes lock window

        $remaining = RateLimiter::remaining($rateLimitKey, 5);

        $this->logSecurityEvent(
            $user, 'failed_otp', 'fail', 
            "Failed OTP verification code attempt: '{$code}'. Remaining attempts: {$remaining}.", $request
        );

        return [
            'status' => false,
            'message' => "The verification code is incorrect. You have {$remaining} attempts remaining."
        ];
    }

    /**
     * Verify a recovery code and log the usage
     */
    public function verifyRecoveryCode(User $user, string $code, Request $request): bool
    {
        $verified = TwoFactorService::verifyRecoveryCode($user, $code);

        if ($verified) {
            $this->logSecurityEvent(
                $user, 'recovery_code_used', 'success', 
                "Hashed recovery code utilized to authorize session.", $request
            );
            return true;
        }

        $this->logSecurityEvent(
            $user, 'failed_recovery_code', 'fail', 
            "Failed backup recovery token verification attempt.", $request
        );

        return false;
    }

    /**
     * Trust the current device/browser for 30 days
     */
    public function trustDevice(User $user, Request $request, ?string $deviceName = null): void
    {
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $ip = $request->ip();
        $userAgent = $request->userAgent();

        if (empty($deviceName)) {
            $deviceName = $this->parseDeviceName($userAgent);
        }

        // Store in DB
        TrustedDevice::create([
            'user_id' => $user->id,
            'device_name' => $deviceName,
            'ip_address' => $ip,
            'device_token' => $hashedToken,
            'location' => 'Local Network',
            'last_active_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        // Queue encrypted cookie for 30 days (43200 minutes)
        Cookie::queue(Cookie::make('device_trust_token', $rawToken, 43200, null, null, false, true));

        $this->logSecurityEvent(
            $user, 'device_trusted', 'success', 
            "Device '{$deviceName}' registered and trusted successfully.", $request
        );
    }

    /**
     * Check if the incoming request comes from a trusted device
     */
    public function isDeviceTrusted(User $user, Request $request): bool
    {
        $rawToken = $request->cookie('device_trust_token');

        if (empty($rawToken)) {
            return false;
        }

        $hashedToken = hash('sha256', $rawToken);

        $trustedDevice = TrustedDevice::where('user_id', $user->id)
            ->where('device_token', $hashedToken)
            ->where('expires_at', '>', now())
            ->first();

        if ($trustedDevice) {
            $trustedDevice->update([
                'last_active_at' => now(),
                'ip_address' => $request->ip(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Revoke a trusted device
     */
    public function revokeDevice(User $user, int $deviceId, Request $request): void
    {
        $device = TrustedDevice::where('user_id', $user->id)->findOrFail($deviceId);
        $deviceName = $device->device_name;

        $device->delete();

        $this->logSecurityEvent(
            $user, 'device_revoked', 'success', 
            "Device '{$deviceName}' revoked successfully by user request.", $request
        );
    }

    /**
     * Helper to write security audit logs
     */
    public function logSecurityEvent(User $user, string $eventType, string $status, ?string $description = null, ?Request $request = null): void
    {
        $request = $request ?? request();

        SecurityLog::create([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'ip_address' => $request->ip() ?? '127.0.0.1',
            'user_agent' => $request->userAgent() ?? 'Unknown Agent',
            'status' => $status,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    /**
     * Dynamic user agent parser helper
     */
    protected function parseDeviceName(string $userAgent): string
    {
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        if (stripos($userAgent, 'windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'macintosh') !== false || stripos($userAgent, 'mac os x') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($userAgent, 'iphone') !== false) {
            $os = 'iPhone';
        } elseif (stripos($userAgent, 'ipad') !== false) {
            $os = 'iPad';
        } elseif (stripos($userAgent, 'android') !== false) {
            $os = 'Android';
        }

        if (stripos($userAgent, 'chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($userAgent, 'firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($userAgent, 'safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'edge') !== false) {
            $browser = 'Edge';
        }

        return "$os - $browser";
    }
}
