<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\Notification\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class TwoFactorOtpService
{
    /**
     * Generate and send OTP via the specified channel.
     */
    public static function sendOtp(User $user, string $channel): array
    {
        if (!in_array($channel, ['sms', 'email'])) {
            return ['status' => false, 'message' => 'Invalid OTP channel.'];
        }

        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "2fa_otp:{$user->id}:{$channel}";
        Cache::put($cacheKey, $code, now()->addMinutes(5));

        if ($channel === 'sms') {
            $phone = $user->profile->phone ?? null;
            if (empty($phone)) {
                return ['status' => false, 'message' => 'No phone number on file. Add a phone number first.'];
            }

            $message = "Your JobPortal verification code is: {$code}. It expires in 5 minutes.";
            $sms = app(SmsService::class)->sendSms($phone, $message);

            if ($sms['status'] === 'success') {
                return ['status' => true, 'message' => "OTP sent to {$phone}"];
            }
            return ['status' => false, 'message' => 'Failed to send SMS. ' . ($sms['message'] ?? '')];
        }

        // Email channel
        Mail::raw("Your JobPortal verification code is: {$code}. It expires in 5 minutes.", function ($mail) use ($user) {
            $mail->to($user->email)
                ->subject('Your Verification Code')
                ->from('noreply@jobportal.com', 'JobPortal');
        });

        return ['status' => true, 'message' => "OTP sent to {$user->email}"];
    }

    /**
     * Verify an OTP code from cache.
     */
    public static function verifyOtp(User $user, string $channel, string $code): bool
    {
        $cacheKey = "2fa_otp:{$user->id}:{$channel}";
        $stored = Cache::get($cacheKey);

        if ($stored && hash_equals($stored, $code)) {
            Cache::forget($cacheKey);
            return true;
        }

        return false;
    }
}
