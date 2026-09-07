<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class VerificationSettingsPanel extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.verification-settings-panel';
    protected static ?string $title = 'Verification Settings';
    protected static ?string $slug = 'system-settings/verification-settings';
    protected static bool $shouldRegisterNavigation = false; // Accessible via Dashboard Cards Hub

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $data[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        }
        
        // Setup defaults if not exist
        $defaults = [
            'verification_enabled' => '1',
            'verification_nid_enabled' => '1',
            'verification_phone_enabled' => '1',
            'verification_email_enabled' => '1',
            'verification_employer_enabled' => '1',
            'nid_require_front' => '1',
            'nid_require_back' => '0', // back NID optional by default
            'nid_ai_enabled' => '1',
            'nid_ai_confidence_threshold' => '75',
            'verification_fee_candidate' => '20',
            'verification_fee_employer' => '50',
            'verification_refund_on_rejection' => '1',
            'verification_reminder_interval_hours' => '24',
            'verification_reminder_max_count' => '5',
            'verification_require_to_apply' => '0',
            'verification_require_to_post' => '0',
            'verification_restrict_wallet' => '1',
            'verification_restrict_remote_escrow' => '1',
            'nid_api_client_id' => '',
            'nid_api_secret_token' => '',
            'nid_api_access_key' => '',
            'nid_api_endpoint_url' => '',
        ];

        foreach ($defaults as $key => $val) {
            if (!isset($data[$key])) {
                $data[$key] = $val;
            }
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Verification Options')->tabs([

                Forms\Components\Tabs\Tab::make('Global Toggles')->schema([
                    Forms\Components\Section::make('Ecosystem Activations')
                        ->description('Configure which parts of the verification ecosystem are active.')
                        ->schema([
                            Forms\Components\Toggle::make('verification_enabled')
                                ->label('Enable Entire Verification System')
                                ->helperText('Master switch. If disabled, all verification checks and blocks are turned off globally.'),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Toggle::make('verification_nid_enabled')
                                    ->label('Enable NID Card Verification'),
                                Forms\Components\Toggle::make('verification_phone_enabled')
                                    ->label('Enable Phone OTP Verification'),
                                Forms\Components\Toggle::make('verification_email_enabled')
                                    ->label('Enable Email OTP Verification'),
                                Forms\Components\Toggle::make('verification_employer_enabled')
                                    ->label('Enable Employer Company Verification'),
                            ]),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('NID Options')->schema([
                    Forms\Components\Section::make('NID OCR & AI Settings')
                        ->description('Adjust the accuracy, document requirements, and AI behavior for NID card reviews.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Toggle::make('nid_require_front')
                                    ->label('Require Front NID Image')
                                    ->default(true),
                                Forms\Components\Toggle::make('nid_require_back')
                                    ->label('Require Back NID Image (Optional)')
                                    ->default(false),
                                Forms\Components\Toggle::make('nid_ai_enabled')
                                    ->label('Enable AI Auto-Verification Engine')
                                    ->helperText('Uses failover LLM matching for OCR validation and facial similarity checks.'),
                                Forms\Components\TextInput::make('nid_ai_confidence_threshold')
                                    ->label('Minimum AI Confidence Threshold (%)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->default(75)
                                    ->helperText('Requests below this score are automatically sent to the manual review fallback queue.'),
                            ]),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Wallet Billing')->schema([
                    Forms\Components\Section::make('Identity verification fees')
                        ->description('Define verification charges, retries, and rejection refund settings.')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('verification_fee_candidate')
                                    ->label('Candidate NID Fee (BDT)')
                                    ->numeric()
                                    ->default(20)
                                    ->required(),
                                Forms\Components\TextInput::make('verification_fee_employer')
                                    ->label('Employer Company Fee (BDT)')
                                    ->numeric()
                                    ->default(50)
                                    ->required(),
                                Forms\Components\Toggle::make('verification_refund_on_rejection')
                                    ->label('Refund Fee on Rejection')
                                    ->helperText('If enabled, rejection by AI/Admin will automatically credit the fee back to the user wallet.'),
                            ]),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Reminders')->schema([
                    Forms\Components\Section::make('Verification Reminder Scheduler')
                        ->description('Schedule in-app and email reminders to encourage users to get verified.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('verification_reminder_interval_hours')
                                    ->label('Reminder Interval Frequency (Hours)')
                                    ->numeric()
                                    ->default(24)
                                    ->required(),
                                Forms\Components\TextInput::make('verification_reminder_max_count')
                                    ->label('Maximum Reminders to Send')
                                    ->numeric()
                                    ->default(5)
                                    ->required()
                                    ->helperText('Stops sending automated reminders after this limit is reached.'),
                            ]),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('Restrictions')->schema([
                    Forms\Components\Section::make('Access Control Settings')
                        ->description('Restrict non-verified candidate/employer access to lock malicious behavior.')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Toggle::make('verification_require_to_apply')
                                    ->label('Require Verification to Apply Jobs')
                                    ->helperText('Candidates must be fully verified to submit standard/remote job proposals.'),
                                Forms\Components\Toggle::make('verification_require_to_post')
                                    ->label('Require Verification to Post Jobs')
                                    ->helperText('Employers must be fully verified to publish active job advertisements.'),
                                Forms\Components\Toggle::make('verification_restrict_wallet')
                                    ->label('Restrict Wallet Transfers & Withdrawals')
                                    ->helperText('Locks withdrawal requests and ads ledger debits for unverified accounts.'),
                                Forms\Components\Toggle::make('verification_restrict_remote_escrow')
                                    ->label('Restrict Escrow Remote Workspace Credentials')
                                    ->helperText('Blocks accessing or sharing secure workspace credentials without verification.'),
                            ]),
                        ]),
                ]),

                Forms\Components\Tabs\Tab::make('NID Server API')->schema([
                    Forms\Components\Section::make('NID Server API Settings')
                        ->description('Configure Global Client ID, Secret Token, and custom endpoints.')
                        ->schema([
                            Forms\Components\Grid::make(1)->schema([
                                Forms\Components\TextInput::make('nid_api_client_id')
                                    ->label('Global Client ID')
                                    ->placeholder('e.g. NX-2B087FF79C7B')
                                    ->required(),
                                Forms\Components\TextInput::make('nid_api_secret_token')
                                    ->label('Global Secret Token')
                                    ->placeholder('e.g. a2f1749a9b9d41678575faa4cad85642')
                                    ->password()
                                    ->revealable()
                                    ->required(),
                                Forms\Components\TextInput::make('nid_api_access_key')
                                    ->label('GLOBAL ACCESS KEY')
                                    ->placeholder('e.g. TlgtMkIwODdGRjc5QzdCOmEyZjE3NDlhOWI5ZDQxNjc4NTc1ZmFhNGNhZDg1NjQy')
                                    ->required(),
                                Forms\Components\Textarea::make('nid_api_endpoint_url')
                                    ->label('Endpoint URL')
                                    ->placeholder('https://api.nid-verification.gov/api.php?key=...')
                                    ->required()
                                    ->rows(3),
                            ]),
                        ]),
                ]),

            ])
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Notification::make()->title('Verification system settings updated successfully!')->success()->send();
    }
}
