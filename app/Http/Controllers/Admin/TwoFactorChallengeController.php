<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorService;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class TwoFactorChallengeController extends Controller
{
    /**
     * Display the 2FA verify challenge or the onboarding setup page
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        if (session()->get('filament.2fa.verified')) {
            return redirect('/');
        }

        // If user already has 2FA confirmed and active, display challenge form
        if ($user->has2faEnabled()) {
            return view('admin.two-factor-challenge', [
                'mode' => 'challenge',
                'user' => $user
            ]);
        }

        // If 2FA is not enabled, redirect to admin dashboard (since 2FA is optional)
        return redirect('/');
    }

    /**
     * Handle submission of OTP verify or setup activation
     */
    public function verify(Request $request)
    {
        $user = Auth::user();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $fingerprint = hash('sha256', $userAgent . $ip);

        // Verification mode
        if ($user->has2faEnabled()) {
            $request->validate([
                'code' => 'required|string'
            ]);

            $code = trim($request->code);

            // 1. Verify standard OTP
            $verified = TwoFactorService::verifyCode($user->two_factor_secret, $code);

            // 2. Fallback: Verify recovery codes
            if (!$verified) {
                $verified = TwoFactorService::verifyRecoveryCode($user, $code);
                if ($verified) {
                    SecurityAuditService::logEvent(
                        $user->id, $ip, $userAgent, $fingerprint, 'recovery_code_used', 
                        10, false, 'Recovery code successfully used to authorize session.'
                    );
                }
            }

            if ($verified) {
                // Set last verified timestamp and flag session
                $user->update(['last_2fa_verified_at' => now()]);
                session()->put('filament.2fa.verified', true);
                
                // Track device footprint
                SecurityAuditService::logEvent(
                    $user->id, $ip, $userAgent, $fingerprint, 'successful_login', 
                    0, false, 'Filament admin 2FA verification completed successfully.'
                );

                return redirect('/');
            }

            // OTP verify failed: rate-limit and alert
            SecurityAuditService::logEvent(
                $user->id, $ip, $userAgent, $fingerprint, 'failed_otp', 
                30, false, "Failed 2FA code verification attempt: '{$code}'."
            );

            return back()->withErrors(['code' => 'The provided verification code or recovery token is invalid.']);
        }

        // Onboarding Setup confirmation mode
        $request->validate([
            'code' => 'required|string',
            'secret' => 'required|string'
        ]);

        $secret = $request->secret;
        $code = trim($request->code);

        if (TwoFactorService::verifyCode($secret, $code)) {
            // Confirmation success! Save secret, enable 2FA and issue recovery codes
            $user->two_factor_secret = $secret;
            $user->two_factor_confirmed_at = now();
            $user->two_factor_enabled = true;
            $user->save();

            // Clear temp secret
            session()->forget('filament.2fa.setup_secret');
            
            // Mark session as verified
            session()->put('filament.2fa.verified', true);
            
            // Generate recovery codes list
            $recoveryCodes = $user->generateRecoveryCodes();

            SecurityAuditService::logEvent(
                $user->id, $ip, $userAgent, $fingerprint, '2fa_enabled', 
                0, false, 'Two Factor Authentication fully configured and activated.'
            );

            // Redirect to a dashboard flash screen displaying recovery codes
            return redirect()->route('filament.admin.two-factor-challenge')->with([
                'success' => 'Two-Factor Authentication is now fully active! Save these recovery codes safely:',
                'recovery_codes' => $recoveryCodes
            ]);
        }

        return back()->withErrors(['code' => 'The validation code is incorrect. Scan the QR code again.']);
    }
}
