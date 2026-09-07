<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\User;
use App\Models\TrustedDevice;
use App\Models\SecurityLog;
use App\Services\Security\SecurityAuditService;
use App\Services\Security\TwoFactorSecurityService;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecuritySettingsPanel extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.security-settings-panel';
    protected static ?string $title = 'MFA & Device Security';
    protected static ?string $slug = 'system-settings/security-settings';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public ?array $found_user_details = null;

    // 2FA state properties
    public bool $twoFactorEnabled = false;
    public bool $isSettingUp2fa = false;
    public string $setupSecret = '';
    public string $qrCodeUrl = '';
    public array $recoveryCodes = [];
    public bool $recoveryCodesRevealed = false;

    public function mount()
    {
        $user = Auth::user();
        $this->twoFactorEnabled = $user->has2faEnabled();

        // Load global 2FA toggles
        $globalReq = Setting::where('key', 'two_factor_required_globally')->first();
        $two_factor_required_globally = $globalReq ? filter_var($globalReq->value, FILTER_VALIDATE_BOOLEAN) : false;

        // Load role list
        $roleReq = Setting::where('key', 'two_factor_required_roles')->first();
        $two_factor_required_roles = $roleReq ? array_map('trim', explode(',', $roleReq->value)) : ['super_admin', 'admin'];

        $this->form->fill([
            'two_factor_required_globally' => $two_factor_required_globally,
            'two_factor_required_roles' => $two_factor_required_roles,
            'user_search_email' => '',
            'twoFactorCode' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        $tabs = [
            Forms\Components\Tabs\Tab::make('My Security Profile')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            // Status Section (Col span 2)
                            Forms\Components\Section::make('2FA Status')
                                ->description('Configure multi-factor authentication to secure your platform account logins.')
                                ->columnSpan(2)
                                ->schema([
                                    Forms\Components\Placeholder::make('2fa_status')
                                        ->label('Authenticator App')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='flex items-center justify-between border-b border-gray-150 dark:border-gray-800 pb-4'>
                                                <div>
                                                    <p class='text-xs text-gray-500 dark:text-gray-400 mt-1'>Use a secure app to generate temporary verification codes.</p>
                                                </div>
                                                <div>
                                                    " . ($this->twoFactorEnabled 
                                                        ? '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">Active</span>'
                                                        : '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Disabled</span>'
                                                    ) . "
                                                </div>
                                            </div>
                                        ")),

                                    // setup flow view
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

                                    // code input
                                    Forms\Components\TextInput::make('twoFactorCode')
                                        ->label('Verification Code')
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

                                    // Standard status actions
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('start2faSetup')
                                            ->label('Set up 2FA')
                                            ->color('primary')
                                            ->visible(fn () => !$this->twoFactorEnabled && !$this->isSettingUp2fa)
                                            ->action(fn () => $this->start2faSetup()),

                                        Forms\Components\Actions\Action::make('turnOff2fa')
                                            ->label('Turn off 2FA')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->modalHeading('Disable Two-Factor Authentication')
                                            ->modalDescription('Are you sure you want to disable 2FA? This will decrease your account security.')
                                            ->visible(fn () => $this->twoFactorEnabled && !$this->isSettingUp2fa)
                                            ->action(fn () => $this->turnOff2fa()),
                                    ])->visible(fn () => !$this->isSettingUp2fa),
                                ]),

                            // Recovery codes card (Col span 1)
                            Forms\Components\Section::make('Backup Recovery Codes')
                                ->description('Save backup recovery codes in a secure offline vault.')
                                ->columnSpan(1)
                                ->schema([
                                    Forms\Components\Placeholder::make('recovery_alert')
                                        ->content(fn () => new \Illuminate\Support\HtmlString("
                                            <div class='p-3 rounded-lg bg-danger-500/10 border border-danger-500/20 text-danger-600 dark:text-danger-400 text-xs font-semibold leading-relaxed mb-4'>
                                                ⚠️ WARNING: Recovery tokens are hashed. Generating new ones invalidates all previous ones immediately.
                                            </div>
                                        ")),

                                    Forms\Components\Placeholder::make('recovery_codes')
                                        ->visible(fn () => $this->twoFactorEnabled)
                                        ->content(function () {
                                            if (!$this->recoveryCodesRevealed) {
                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg text-center'>
                                                        <span class='text-xs text-gray-500'>•••• •••• •••• ••••</span>
                                                    </div>
                                                ");
                                            }

                                            $codesHtml = '';
                                            foreach ($this->recoveryCodes as $code) {
                                                $codesHtml .= "
                                                    <div class='flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg font-mono text-xs'>
                                                        <span>{$code}</span>
                                                        <button type='button' class='text-[10px] font-bold text-primary-600 hover:underline' onclick=\"navigator.clipboard.writeText('{$code}'); alert('Recovery code copied');\">Copy</button>
                                                    </div>
                                                ";
                                            }

                                            return new \Illuminate\Support\HtmlString("
                                                <div class='space-y-2'>
                                                    {$codesHtml}
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('revealRecoveryCodes')
                                            ->label(fn () => $this->recoveryCodesRevealed ? 'Hide Codes' : 'Reveal Codes')
                                            ->color('gray')
                                            ->visible(fn () => $this->twoFactorEnabled)
                                            ->action(fn () => $this->revealRecoveryCodes()),

                                        Forms\Components\Actions\Action::make('regenerateRecoveryCodes')
                                            ->label('Generate New Codes')
                                            ->color('primary')
                                            ->requiresConfirmation()
                                            ->modalHeading('Regenerate Recovery Codes')
                                            ->modalDescription('Generating new codes invalidates previous tokens.')
                                            ->visible(fn () => $this->twoFactorEnabled)
                                            ->action(fn () => $this->regenerateRecoveryCodes()),
                                    ])->visible(fn () => $this->twoFactorEnabled),
                                ])
                        ])
                ])
        ];

        if (Auth::user()->hasAnyRole(['super_admin', 'admin']) || in_array(Auth::user()->role, ['super_admin', 'admin'])) {
            $tabs[] = Forms\Components\Tabs\Tab::make('Global Settings & Overrides')
                ->icon('heroicon-o-cog')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Section::make('Global 2FA Configurations')
                                ->description('Configure global enforcement settings across different user roles.')
                                ->columnSpan(2)
                                ->schema([
                                    Forms\Components\Toggle::make('two_factor_required_globally')
                                        ->label('Require 2FA Globally')
                                        ->helperText('Force all platform users (Admins, Moderators, Employers, Candidates) to configure and use 2FA.')
                                        ->live(),

                                    Forms\Components\CheckboxList::make('two_factor_required_roles')
                                        ->label('Enforce 2FA for Selected Roles Only')
                                        ->helperText('If global enforcement is off, users with these roles will still be forced to enable 2FA during panel access.')
                                        ->options([
                                            'super_admin' => 'Super Admin',
                                            'admin' => 'Admin',
                                            'moderator' => 'Moderator',
                                            'manager' => 'Manager',
                                            'employer' => 'Employer',
                                            'candidate' => 'Candidate',
                                        ])
                                        ->columns(2)
                                        ->visible(fn (Forms\Get $get) => ! $get('two_factor_required_globally')),
                                ]),

                            Forms\Components\Section::make('User Override Desk')
                                ->description('Manage user MFA profiles and disable locked controls.')
                                ->columnSpan(1)
                                ->schema([
                                    Forms\Components\TextInput::make('user_search_email')
                                        ->label('Search User Email')
                                        ->placeholder('e.g. admin@jobportal.com')
                                        ->email()
                                        ->suffixAction(
                                            Forms\Components\Actions\Action::make('searchUser')
                                                ->icon('heroicon-m-magnifying-glass')
                                                ->action(fn () => $this->searchUser())
                                        ),

                                    Forms\Components\Placeholder::make('found_user_details')
                                        ->label('Search Result')
                                        ->visible(fn () => !empty($this->found_user_details))
                                        ->content(function () {
                                            if (!$this->found_user_details) {
                                                return null;
                                            }

                                            $details = $this->found_user_details;
                                            $twoFactorStatus = $details['two_factor_enabled']
                                                ? '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">Active</span>'
                                                : '<span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Disabled</span>';

                                            return new \Illuminate\Support\HtmlString("
                                                <div class=\"space-y-3 rounded-lg border border-gray-200 p-4 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/50\">
                                                    <div class=\"border-b border-gray-200 pb-2 dark:border-gray-700\">
                                                        <div class=\"text-sm font-semibold text-gray-900 dark:text-white\">{$details['name']}</div>
                                                        <div class=\"text-xs text-gray-500 dark:text-gray-400\">{$details['email']}</div>
                                                    </div>
                                                    <div class=\"grid grid-cols-2 gap-2 text-xs\">
                                                        <div class=\"text-gray-500 dark:text-gray-400\">Role:</div>
                                                        <div class=\"font-medium text-gray-900 dark:text-white\">" . strtoupper($details['role']) . "</div>
                                                        
                                                        <div class=\"text-gray-500 dark:text-gray-400\">2FA Active:</div>
                                                        <div>{$twoFactorStatus}</div>
                                                        
                                                        <div class=\"text-gray-500 dark:text-gray-400\">Last Verified:</div>
                                                        <div class=\"font-medium text-gray-900 dark:text-white\">{$details['last_verified']}</div>
                                                    </div>
                                                </div>
                                            ");
                                        }),

                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('forceDisable2fa')
                                            ->label('Force Disable 2FA')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->modalHeading('Force Disable 2FA')
                                            ->modalDescription('Are you sure you want to FORCE DISABLE 2FA for this user?')
                                            ->action(fn () => $this->forceDisable2fa($this->found_user_details['id'])),

                                        Forms\Components\Actions\Action::make('forceResetRecoveryCodes')
                                            ->label('Force Reset Recovery Codes')
                                            ->color('warning')
                                            ->requiresConfirmation()
                                            ->modalHeading('Force Reset Recovery Codes')
                                            ->modalDescription('Are you sure you want to FORCE RESET recovery codes?')
                                            ->visible(fn () => !empty($this->found_user_details) && $this->found_user_details['two_factor_enabled'])
                                            ->action(fn () => $this->forceResetRecoveryCodes($this->found_user_details['id'])),
                                    ])->visible(fn () => !empty($this->found_user_details))
                                    ->alignEnd(),
                                ]),
                        ])
                ]);
        }

        return $form->schema([
            Forms\Components\Tabs::make('Security Settings Options')->tabs($tabs)
        ])->statePath('data');
    }

    protected ?Table $securityLogsTableInstance = null;

    public function __get($property): mixed
    {
        if ($property === 'securityLogsTable') {
            return $this->getSecurityLogsTable();
        }

        return parent::__get($property);
    }

    public function getSecurityLogsTable(): Table
    {
        if ($this->securityLogsTableInstance) {
            return $this->securityLogsTableInstance;
        }

        $this->securityLogsTableInstance = \Filament\Tables\Actions\Action::configureUsing(
            \Closure::fromCallable([$this, 'configureTableAction']),
            fn (): Table => \Filament\Tables\Actions\BulkAction::configureUsing(
                \Closure::fromCallable([$this, 'configureTableBulkAction']),
                fn (): Table => $this->securityLogsTable($this->makeTable()),
            ),
        );

        return $this->securityLogsTableInstance;
    }

    protected function getTables(): array
    {
        return [
            'table',
            'securityLogsTable',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TrustedDevice::query()->where('user_id', Auth::id()))
            ->columns([
                Tables\Columns\TextColumn::make('device_name')
                    ->label('Device Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('last_active_at')
                    ->label('Last Active')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->default('Unknown'),
            ])
            ->actions([
                Tables\Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->action(fn (TrustedDevice $record) => $this->revokeDevice($record->id)),
            ]);
    }

    public function securityLogsTable(Table $table): Table
    {
        return $table
            ->query(SecurityLog::query()->where('user_id', Auth::id()))
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '2fa_enabled', 'successful_login', 'successful_otp' => 'success',
                        '2fa_disabled', 'failed_otp', 'failed_recovery_code' => 'danger',
                        'device_revoked', 'recovery_code_used', 'device_trusted' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Device / User Agent')
                    ->limit(45),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'fail' => 'danger',
                        'suspicious' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function saveSettings()
    {
        $formData = $this->form->getState();

        Setting::updateOrCreate(
            ['key' => 'two_factor_required_globally'],
            ['value' => ($formData['two_factor_required_globally'] ?? false) ? 'true' : 'false']
        );

        Setting::updateOrCreate(
            ['key' => 'two_factor_required_roles'],
            ['value' => implode(',', $formData['two_factor_required_roles'] ?? [])]
        );

        Notification::make()
            ->title('Global security settings saved successfully!')
            ->success()
            ->send();
    }

    public function searchUser()
    {
        $this->found_user_details = null;

        $email = $this->data['user_search_email'] ?? '';

        if (empty($email)) {
            Notification::make()->title('Please enter an email address.')->warning()->send();
            return;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            Notification::make()->title('No user found with this email.')->danger()->send();
            return;
        }

        $this->found_user_details = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first() ?? $user->role ?? 'N/A',
            'two_factor_enabled' => $user->two_factor_enabled,
            'two_factor_confirmed' => $user->two_factor_confirmed_at ? $user->two_factor_confirmed_at->diffForHumans() : 'No',
            'last_verified' => $user->last_2fa_verified_at ? $user->last_2fa_verified_at->toDateTimeString() : 'Never',
        ];
    }

    public function forceDisable2fa(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
            'trusted_devices' => null
        ]);

        Notification::make()->title('2FA successfully disabled for this user.')->success()->send();
        $this->searchUser();
    }

    public function forceResetRecoveryCodes(int $userId)
    {
        $user = User::findOrFail($userId);
        
        if (!$user->two_factor_enabled) {
            Notification::make()->title('Cannot reset recovery codes: User does not have 2FA enabled.')->warning()->send();
            return;
        }

        $codes = $user->generateRecoveryCodes();

        Notification::make()
            ->title('Recovery codes successfully reset and forced to regenerate!')
            ->success()
            ->body('New backup tokens generated. Advise user to view settings to copy them.')
            ->send();

        $this->searchUser();
    }

    public function start2faSetup()
    {
        $user = Auth::user();
        $service = app(TwoFactorSecurityService::class);
        $this->setupSecret = $service->generateSecretKey();
        $this->qrCodeUrl = $service->getQrCodeUrl($user, $this->setupSecret);
        $this->isSettingUp2fa = true;
        $this->data['twoFactorCode'] = '';
    }

    public function confirm2faSetup()
    {
        $code = $this->data['twoFactorCode'] ?? '';
        
        if (empty($code)) {
            Notification::make()->title('Verification code is required.')->warning()->send();
            return;
        }

        $user = Auth::user();
        $service = app(TwoFactorSecurityService::class);

        $result = $service->verifyOtp($user, $this->setupSecret, $code, request());

        if ($result['status']) {
            $user->two_factor_secret = $this->setupSecret;
            $user->two_factor_confirmed_at = now();
            $user->two_factor_enabled = true;
            $user->save();

            // Set session verification
            session()->put('filament.2fa.verified', true);

            // Generate recovery codes
            $rawCodes = $user->generateRecoveryCodes();
            $this->recoveryCodes = $rawCodes;
            $this->recoveryCodesRevealed = true;

            $this->isSettingUp2fa = false;
            $this->twoFactorEnabled = true;
            $this->data['twoFactorCode'] = '';

            $service->logSecurityEvent(
                $user, '2fa_enabled', 'success', 
                "Two Factor Authentication fully configured and activated.", request()
            );

            Notification::make()
                ->title('Two-Factor Authentication Enabled')
                ->success()
                ->send();
        } else {
            Notification::make()->title('Invalid verification code. Please try again.')->danger()->send();
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
        $this->recoveryCodesRevealed = false;

        $service = app(TwoFactorSecurityService::class);
        $service->logSecurityEvent(
            $user, '2fa_disabled', 'danger', 
            "Two Factor Authentication manually deactivated by user.", request()
        );

        Notification::make()
            ->title('Two-Factor Authentication Disabled')
            ->warning()
            ->send();
    }

    public function revealRecoveryCodes()
    {
        if (!$this->recoveryCodesRevealed) {
            $user = Auth::user();
            $this->recoveryCodesRevealed = true;
            if (empty($this->recoveryCodes) && $user->two_factor_recovery_codes) {
                try {
                    $decrypted = json_decode(decrypt($user->two_factor_recovery_codes), true);
                    $this->recoveryCodes = is_array($decrypted) ? $decrypted : [];
                } catch (\Exception $e) {
                    $this->recoveryCodes = [];
                }
            }
        } else {
            $this->recoveryCodesRevealed = false;
        }
    }

    public function regenerateRecoveryCodes()
    {
        $user = Auth::user();
        $rawCodes = $user->generateRecoveryCodes();
        $this->recoveryCodes = $rawCodes;
        $this->recoveryCodesRevealed = true;

        $service = app(TwoFactorSecurityService::class);
        $service->logSecurityEvent(
            $user, 'recovery_codes_regenerated', 'success', 
            "Backup recovery codes regenerated successfully.", request()
        );

        Notification::make()
            ->title('Recovery Codes Regenerated')
            ->success()
            ->send();
    }

    public function revokeDevice(int $deviceId)
    {
        $user = Auth::user();
        $service = app(TwoFactorSecurityService::class);
        $service->revokeDevice($user, $deviceId, request());

        Notification::make()
            ->title('Trusted Device Revoked')
            ->success()
            ->send();
    }
}
