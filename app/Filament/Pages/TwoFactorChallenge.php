<?php

namespace App\Filament\Pages;

use App\Services\Security\TwoFactorSecurityService;
use Filament\Pages\SimplePage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallenge extends SimplePage
{
    protected static bool $isDiscovered = false;

    protected static string $view = 'admin.two-factor-challenge';

    public ?string $code = '';

    public function mount()
    {
        $user = Auth::user();

        if (session()->get('filament.2fa.verified')) {
            return redirect('/');
        }

        if (!$user || !$user->has2faEnabled()) {
            return redirect('/');
        }
    }

    public function getHeading(): string
    {
        return 'Two-Factor Verification';
    }

    public function getSubHeading(): string
    {
        return 'Please enter your 6-digit verification code or backup recovery token to confirm session.';
    }

    public function verify()
    {
        $this->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $service = app(TwoFactorSecurityService::class);

        // 1. Verify standard OTP via service
        $result = $service->verifyOtp($user, $user->two_factor_secret, $this->code, request());

        $verified = $result['status'];

        // 2. Fallback: Verify recovery codes
        if (!$verified) {
            $verified = $service->verifyRecoveryCode($user, $this->code, request());
        }

        if ($verified) {
            $user->update(['last_2fa_verified_at' => now()]);
            session()->put('filament.2fa.verified', true);

            // Establish device trust
            $service->trustDevice($user, request());

            Notification::make()
                ->title('Verification Successful')
                ->success()
                ->send();

            return redirect('/');
        }

        $this->addError('code', 'The provided verification code or recovery token is invalid.');
    }
}
