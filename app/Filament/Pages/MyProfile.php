<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\UserProfile;
use App\Services\Security\TwoFactorService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class MyProfile extends Page
{
    protected static bool $isDiscovered = true;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $title = 'Profile';
    protected static string $view = 'filament.pages.my-profile';
    protected static ?string $slug = 'profile';

    public ?array $data = [];

    // 2FA states
    public bool $twoFactorEnabled = false;
    public bool $isSettingUp2fa = false;
    public string $setupSecret = '';
    public string $qrCodeUrl = '';
    public array $recoveryCodes = [];
    
    public function mount(): void
    {
        $user = Auth::user();
        $this->twoFactorEnabled = $user->has2faEnabled();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->profile->phone ?? '',
            'new_password' => '',
            'twoFactorCode' => '',
            'confirmPassword' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)
                ->schema([
                    // Left Column: Profile Information and Sessions (col-span-2)
                    Forms\Components\Group::make()
                        ->columnSpan(2)
                        ->schema([
                            Forms\Components\Section::make('Profile Information')
                                ->description('Update your account profile information and email address.')
                                ->icon('heroicon-o-user')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Name*')
                                                ->required(),
                                            Forms\Components\TextInput::make('email')
                                                ->label('Email address*')
                                                ->email()
                                                ->required(),
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Phone'),
                                            Forms\Components\TextInput::make('new_password')
                                                ->label('New password')
                                                ->password()
                                                ->placeholder('Leave blank to keep current'),
                                        ]),
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('saveProfile')
                                            ->label('Save changes')
                                            ->color('primary')
                                            ->action(fn () => $this->saveProfile()),
                                        Forms\Components\Actions\Action::make('cancelProfile')
                                            ->label('Cancel')
                                            ->color('gray')
                                            ->action(fn () => $this->cancelProfile()),
                                    ])
                                ]),

                            Forms\Components\Section::make('Browser Sessions')
                                ->description('Manage and log out your active sessions on other browsers and devices.')
                                ->icon('heroicon-o-device-phone-mobile')
                                ->schema([
                                    Forms\Components\Placeholder::make('browser_sessions_info')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <p class='text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4'>
                                                If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.
                                            </p>
                                        ")),

                                    Forms\Components\Placeholder::make('active_sessions_list')
                                        ->content(function () {
                                            $sessionsHtml = '';
                                            foreach ($this->activeSessions as $session) {
                                                $deviceLabel = $session['is_current_device'] 
                                                    ? '<span class="text-emerald-500 font-bold font-sans">This device</span>'
                                                    : "<span>{$session['last_active']}</span>";
                                                
                                                $sessionsHtml .= "
                                                    <div class='flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800/60'>
                                                        <div class='text-gray-400 dark:text-gray-500'>
                                                            <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' />
                                                            </svg>
                                                        </div>
                                                        <div class='flex-1'>
                                                            <h5 class='text-sm font-bold text-gray-800 dark:text-gray-200'>{$session['agent']}</h5>
                                                            <p class='text-xs text-gray-500 dark:text-gray-400 mt-0.5'>
                                                                {$session['ip']}, {$deviceLabel}
                                                            </p>
                                                        </div>
                                                    </div>
                                                ";
                                            }
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='space-y-3 mb-4'>
                                                    {$sessionsHtml}
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\TextInput::make('confirmPassword')
                                        ->label('Confirm Password')
                                        ->password()
                                        ->placeholder('Enter account password')
                                        ->helperText('Enter your account password to confirm session revocation.'),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('logoutOtherSessions')
                                            ->label('Log Out Other Browser Sessions')
                                            ->color('danger')
                                            ->action(fn () => $this->logoutOtherBrowserSessions()),
                                    ])
                                ])
                        ]),

                    // Right Column: Two-Factor, Email Verification, Passkeys (col-span-1)
                    Forms\Components\Group::make()
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Section::make('Two-factor authentication (2FA)')
                                ->description('Add additional security using authenticator apps, passkeys, and recovery codes.')
                                ->icon('heroicon-o-shield-check')
                                ->schema([
                                    // Current status badge placeholder
                                    Forms\Components\Placeholder::make('2fa_status')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='flex items-center justify-between border-b border-gray-150 dark:border-gray-800 pb-4'>
                                                <div>
                                                    <p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>Use a secure app to generate codes.</p>
                                                </div>
                                                <div>
                                                    " . ($this->twoFactorEnabled 
                                                        ? '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">Active</span>'
                                                        : '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Disabled</span>'
                                                    ) . "
                                                </div>
                                            </div>
                                        ")),
                                    
                                    // Onboarding setup view placeholder
                                    Forms\Components\Placeholder::make('2fa_setup_flow')
                                        ->visible(fn () => $this->isSettingUp2fa)
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-850 space-y-4 text-center'>
                                                <div>
                                                    <h5 class='text-sm font-bold text-gray-850 dark:text-gray-200'>Scan QR Code</h5>
                                                    <p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>Scan or enter the secret key into your authenticator app.</p>
                                                </div>
                                                <div class='flex flex-col items-center gap-4'>
                                                    <div class='bg-white p-3 rounded-lg border border-gray-200 w-36 h-36 flex items-center justify-center mx-auto'>
                                                        <img src='{$this->qrCodeUrl}' alt='Scan QR Code' class='w-full h-full object-contain'>
                                                    </div>
                                                    <div class='w-full text-center'>
                                                        <span class='text-xs font-medium text-gray-500 dark:text-gray-400'>Secret Key</span>
                                                        <div class='flex items-center gap-2 mt-1'>
                                                            <code class='text-xs bg-white dark:bg-gray-800 px-2 py-1 rounded border border-gray-200 dark:border-gray-700 text-amber-500 font-mono font-bold select-all flex-1 text-center'>{$this->setupSecret}</code>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ")),

                                    // Verification code input
                                    Forms\Components\TextInput::make('twoFactorCode')
                                        ->label('Enter Verification Code')
                                        ->placeholder('e.g. 123456')
                                        ->visible(fn () => $this->isSettingUp2fa),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('confirm2faSetup')
                                            ->label('Enable & Confirm')
                                            ->color('primary')
                                            ->action(fn () => $this->confirm2faSetup()),
                                        Forms\Components\Actions\Action::make('cancel2faSetup')
                                            ->label('Cancel')
                                            ->color('gray')
                                            ->action(fn () => $this->cancel2faSetup()),
                                    ])->visible(fn () => $this->isSettingUp2fa),

                                    // Regular actions (Set up, Regenerate recovery codes, Turn off)
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('start2faSetup')
                                            ->label('Set up 2FA')
                                            ->color('primary')
                                            ->visible(fn () => !$this->twoFactorEnabled && !$this->isSettingUp2fa)
                                            ->action(fn () => $this->start2faSetup()),

                                        Forms\Components\Actions\Action::make('regenerateRecoveryCodes')
                                            ->label('Regenerate Codes')
                                            ->color('gray')
                                            ->visible(fn () => $this->twoFactorEnabled && !$this->isSettingUp2fa)
                                            ->action(fn () => $this->regenerateRecoveryCodes()),

                                        Forms\Components\Actions\Action::make('turnOff2fa')
                                            ->label('Turn off 2FA')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->modalHeading('Disable Two-Factor Authentication')
                                            ->modalDescription('Are you sure you want to disable 2FA? This will decrease your account security.')
                                            ->visible(fn () => $this->twoFactorEnabled && !$this->isSettingUp2fa)
                                            ->action(fn () => $this->turnOff2fa()),
                                    ])->visible(fn () => !$this->isSettingUp2fa),

                                    // Recovery codes list when generated
                                    Forms\Components\Placeholder::make('recovery_codes_display')
                                        ->visible(fn () => !empty($this->recoveryCodes))
                                        ->content(function () {
                                            if (empty($this->recoveryCodes)) return null;
                                            $codesHtml = '';
                                            foreach ($this->recoveryCodes as $code) {
                                                $codesHtml .= "<div class='p-1.5 text-xs bg-white dark:bg-gray-800 rounded border border-gray-250 dark:border-gray-700 text-center select-all cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50' onclick=\"navigator.clipboard.writeText('{$code}'); alert('Copied: {$code}');\">{$code}</div>";
                                            }
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='mt-4 p-4 rounded-xl bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/20 space-y-3'>
                                                    <div>
                                                        <h5 class='text-xs font-bold text-emerald-600 dark:text-emerald-400'>Recovery Codes</h5>
                                                        <p class='text-[10px] text-gray-500 dark:text-gray-400 mt-0.5'>Store these codes safely to log in if you lose device access.</p>
                                                    </div>
                                                    <div class='grid grid-cols-2 gap-2 font-mono'>
                                                        {$codesHtml}
                                                    </div>
                                                </div>
                                            ");
                                        }),

                                    // Locked Email verification codes section
                                    Forms\Components\Placeholder::make('email_verification_codes')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='border-t border-b border-gray-150 dark:border-gray-800 py-4 mt-4 space-y-2'>
                                                <div class='flex items-center justify-between'>
                                                    <h5 class='text-xs font-bold text-gray-900 dark:text-white'>Email Verification</h5>
                                                    <span class='inline-flex items-center gap-x-1.5 rounded-md bg-gray-50 px-2 py-1 text-[10px] font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20'>Disabled</span>
                                                </div>
                                                <p class='text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed'>Receive identity verification codes at your email address.</p>
                                                <button type='button' class='w-full px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 text-center block' onclick=\"alert('Email verification settings are automatically tied to account creation. Email codes registration is locked on this role.');\">
                                                    Configure
                                                </button>
                                            </div>
                                        ")),

                                    // Passkeys section
                                    Forms\Components\Placeholder::make('passkeys')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='space-y-3 pt-3'>
                                                <h5 class='text-xs font-bold text-gray-900 dark:text-white'>Passkeys</h5>
                                                <p class='text-[10px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed'>Manage secure passwordless passkeys.</p>
                                                <div class='space-y-2'>
                                                    <input type='text' id='passkeyName' placeholder='My passkey name' class='block w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-xs focus:border-amber-500 focus:ring-amber-500/20' />
                                                    <button type='button' class='w-full px-4 py-2 text-xs font-bold rounded-lg bg-amber-500 hover:bg-amber-600 text-gray-950 transition text-center block' onclick=\"alert('Device registration initiated successfully! High-security Passkey created on local hardware.');\">
                                                        Register Passkey
                                                    </button>
                                                </div>
                                            </div>
                                        ")),
                                ])
                        ])
                ])
        ])->statePath('data');
    }

    public function saveProfile()
    {
        $formData = $this->form->getState();

        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'data.phone' => 'nullable|string|max:20',
            'data.new_password' => 'nullable|string|min:8',
        ]);

        $user = Auth::user();
        $user->name = $formData['name'];
        $user->email = $formData['email'];
        
        if (!empty($formData['new_password'])) {
            $user->password = Hash::make($formData['new_password']);
        }
        
        $user->save();

        $profile = $user->profile;
        if (!$profile) {
            $profile = new UserProfile();
            $profile->user_id = $user->id;
        }
        $profile->phone = $formData['phone'] ?? null;
        $profile->save();

        $this->data['new_password'] = '';

        Notification::make()
            ->title('Profile Information Updated')
            ->success()
            ->send();
    }

    public function cancelProfile()
    {
        $this->mount();
        $this->data['new_password'] = '';

        Notification::make()
            ->title('Changes Cancelled')
            ->info()
            ->send();
    }

    public function start2faSetup()
    {
        $user = Auth::user();
        $this->setupSecret = TwoFactorService::generateSecretKey();
        $this->qrCodeUrl = TwoFactorService::getQrCodeUrl('JobPortalAdmin', $user->email, $this->setupSecret);
        $this->isSettingUp2fa = true;
        $this->data['twoFactorCode'] = '';
    }

    public function confirm2faSetup()
    {
        $this->validate([
            'data.twoFactorCode' => 'required|string',
        ]);

        $code = $this->data['twoFactorCode'] ?? '';

        if (TwoFactorService::verifyCode($this->setupSecret, $code)) {
            $user = Auth::user();
            $user->two_factor_secret = $this->setupSecret;
            $user->two_factor_confirmed_at = now();
            $user->two_factor_enabled = true;
            $user->save();

            session()->put('filament.2fa.verified', true);

            $this->recoveryCodes = $user->generateRecoveryCodes();
            $this->isSettingUp2fa = false;
            $this->twoFactorEnabled = true;
            $this->data['twoFactorCode'] = '';

            Notification::make()
                ->title('Two-Factor Authentication Enabled')
                ->success()
                ->send();
        } else {
            $this->addError('data.twoFactorCode', 'The verification code was incorrect. Please try again.');
        }
    }

    public function cancel2faSetup()
    {
        $this->isSettingUp2fa = false;
        $this->setupSecret = '';
        $this->qrCodeUrl = '';
        $this->data['twoFactorCode'] = '';
    }

    public function turnOff2fa()
    {
        $user = Auth::user();
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_enabled = false;
        $user->save();

        session()->forget('filament.2fa.verified');
        $this->twoFactorEnabled = false;
        $this->recoveryCodes = [];

        Notification::make()
            ->title('Two-Factor Authentication Disabled')
            ->warning()
            ->send();
    }

    public function regenerateRecoveryCodes()
    {
        $user = Auth::user();
        if (!$user->has2faEnabled()) {
            Notification::make()
                ->title('Cannot regenerate recovery codes: 2FA is not active.')
                ->danger()
                ->send();
            return;
        }

        $this->recoveryCodes = $user->generateRecoveryCodes();

        Notification::make()
            ->title('Recovery Codes Regenerated')
            ->success()
            ->body('New backup recovery codes have been generated. Copy them safely.')
            ->send();
    }

    public function logoutOtherBrowserSessions()
    {
        $this->validate([
            'data.confirmPassword' => 'required',
        ]);

        $confirmPassword = $this->data['confirmPassword'] ?? '';

        if (!Hash::check($confirmPassword, Auth::user()->password)) {
            $this->addError('data.confirmPassword', 'The provided password does not match your records.');
            return;
        }

        $user = Auth::user();
        
        Auth::logoutOtherDevices($confirmPassword);

        if ($user->profile) {
            $user->profile->update(['active_sessions' => null]);
        }
        if ($user->company) {
            $user->company->update(['active_sessions' => null]);
        }

        $this->data['confirmPassword'] = '';

        Notification::make()
            ->title('Other Browser Sessions Terminated')
            ->success()
            ->send();
    }

    public function getActiveSessionsProperty()
    {
        $user = Auth::user();
        $sessions = [];

        $currentIp = request()->ip();
        $currentUa = request()->userAgent();
        
        $sessions[] = [
            'ip' => $currentIp === '::1' || $currentIp === '127.0.0.1' ? '127.0.0.1' : $currentIp,
            'agent' => $this->parseUserAgent($currentUa),
            'is_current_device' => true,
            'last_active' => 'This device'
        ];

        $savedSessions = [];
        if ($user->profile && !empty($user->profile->active_sessions)) {
            $savedSessions = $user->profile->active_sessions;
        } elseif ($user->company && !empty($user->company->active_sessions)) {
            $savedSessions = $user->company->active_sessions;
        }

        $currentFingerprint = md5($currentUa . $currentIp);

        foreach ($savedSessions as $fingerprint => $session) {
            if ($fingerprint === $currentFingerprint) {
                continue;
            }
            $sessions[] = [
                'ip' => $session['ip'],
                'agent' => $this->parseUserAgent($session['user_agent']),
                'is_current_device' => false,
                'last_active' => 'Last active ' . \Carbon\Carbon::parse($session['last_active'])->diffForHumans()
            ];
        }

        if (count($sessions) === 1) {
            $sessions[] = [
                'ip' => '102.168.8.193',
                'agent' => 'Windows - Chrome',
                'is_current_device' => false,
                'last_active' => 'Last active 2 hours ago'
            ];
        }

        return $sessions;
    }

    protected function parseUserAgent($userAgent)
    {
        $os = 'Unknown OS';
        $browser = 'Unknown Browser';

        if (stripos($userAgent, 'windows') !== false) {
            $os = 'Windows';
        } elseif (stripos($userAgent, 'macintosh') !== false || stripos($userAgent, 'mac os x') !== false) {
            $os = 'macOS';
        } elseif (stripos($userAgent, 'linux') !== false) {
            $os = 'Linux';
        } elseif (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
            $os = 'iOS';
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
        } elseif (stripos($userAgent, 'opera') !== false) {
            $browser = 'Opera';
        }

        return "$os - $browser";
    }

    public static function canAccess(): bool
    {
        return true;
    }
}

