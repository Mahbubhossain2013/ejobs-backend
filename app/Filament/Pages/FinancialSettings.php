<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class FinancialSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.financial-settings';
    protected static ?string $title = 'Service Charges & Fees';
    protected static ?string $slug = 'system-settings/financial-settings';
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

        // Apply default fallbacks if missing
        if (!isset($data['remote_job_service_charge'])) {
            $data['remote_job_service_charge'] = 5;
        }
        if (!isset($data['application_fee'])) {
            $data['application_fee'] = 50;
        }
        if (!isset($data['escrow_fee_percent'])) {
            $data['escrow_fee_percent'] = 2;
        }
        if (!isset($data['escrow_fee_payer'])) {
            $data['escrow_fee_payer'] = 'candidate';
        }
        if (!isset($data['min_withdrawal_amount'])) {
            $data['min_withdrawal_amount'] = 500;
        }

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Section::make('Developer Service Charges')
                    ->description('Set developer/freelancer payout configurations')
                    ->schema([
                        Forms\Components\TextInput::make('remote_job_service_charge')
                            ->label('Developer Service Charge Percent (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->helperText('Platform percentage deducted from remote freelancers upon project completion (default 5%)'),
                    ]),
                Forms\Components\Section::make('Application Fees')
                    ->description('Configure candidate job application costs')
                    ->schema([
                        Forms\Components\TextInput::make('application_fee')
                            ->label('Candidate Application Fee (BDT)')
                            ->numeric()
                            ->required()
                            ->prefix('৳')
                            ->helperText('Token fee charged to candidates when submitting proposals (default 50 BDT)'),
                    ]),
                Forms\Components\Section::make('Escrow Protection Settings')
                    ->description('Configure escrow fees and who pays them')
                    ->schema([
                        Forms\Components\TextInput::make('escrow_fee_percent')
                            ->label('Escrow Fee Percent (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->helperText('Service fee charged on escrow amount (default 2%)'),
                        Forms\Components\Select::make('escrow_fee_payer')
                            ->label('Who Pays Escrow Fee?')
                            ->options([
                                'employer' => 'Employer (deducted at lock time from employer balance)',
                                'candidate' => 'Candidate (deducted at release time from candidate payout)',
                            ])
                            ->required()
                            ->default('candidate')
                            ->helperText('Choose who bears the escrow service fee'),
                    ]),
                Forms\Components\Section::make('Wallet Withdrawal Thresholds')
                    ->description('Specify payouts guidelines')
                    ->schema([
                        Forms\Components\TextInput::make('min_withdrawal_amount')
                            ->label('Minimum Withdrawal Amount (BDT)')
                            ->numeric()
                            ->required()
                            ->prefix('৳')
                            ->helperText('Minimum balance required inside wallet for candidates to request BKash / Bank payouts'),
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

        Notification::make()->title('Financial settings and service charges saved successfully!')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_financial_settings');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}
