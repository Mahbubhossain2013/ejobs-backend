<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SmsSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.sms-settings';
    protected static ?string $title = 'SMS Settings';
    protected static ?string $slug = 'system-settings/sms-settings';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all();
        $data = [];
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $data[$setting->key] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $setting->value;
        }
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Global SMS Settings')
                ->description('Configure system-wide SMS delivery. These settings will be used by all brands unless brand overrides are enabled.')
                ->schema([
                    Forms\Components\Toggle::make('sms_enabled')
                        ->label('Enable SMS Sending')
                        ->helperText('Master switch for all outgoing SMS.'),

                    Forms\Components\Select::make('sms_driver')
                        ->label('SMS Driver*')
                        ->options([
                            'bulksmsbd' => 'BulkSMSBD',
                            'mimsms' => 'MimSMS'
                        ])
                        ->default('bulksmsbd')
                        ->required()
                        ->helperText('Choose your preferred SMS delivery method'),

                    Forms\Components\TextInput::make('sms_api_token')
                        ->label('SMS API Token*')
                        ->password()
                        ->required()
                        ->helperText('Enter your API token/credentials for the chosen driver'),

                    Forms\Components\TextInput::make('sms_sender_id')
                        ->label('SMS Sender ID*')
                        ->required()
                        ->helperText('Usually your registered sender ID or brand name'),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('test_sms')
                            ->label('Test Connection')
                            ->color('info')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->form([
                                Forms\Components\TextInput::make('test_number')
                                    ->label('Recipient Phone Number')
                                    ->required()
                                    ->placeholder('+88017XXXXXXXX'),
                            ])
                            ->action(function (array $data) {
                                $recipient = $data['test_number'];
                                $settings = $this->data;

                                if (empty($settings['sms_enabled'])) {
                                    Notification::make()
                                        ->title('SMS is Disabled!')
                                        ->danger()
                                        ->body('Please check "SMS Enabled" toggle, save changes, and try again.')
                                        ->send();
                                    return;
                                }

                                // Dispatch real test SMS through our SmsService
                                $smsService = resolve(\App\Services\Notification\SmsService::class);
                                $result = $smsService->sendSms($recipient, "Test SMS connection check from " . (Setting::where('key', 'site_name')->value('value') ?? 'JobBazar'));
                                
                                if ($result['status'] === 'success') {
                                    Notification::make()
                                        ->title('SMS Sent Successfully!')
                                        ->success()
                                        ->body("Test SMS sent to {$recipient} successfully using {$settings['sms_driver']}.")
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('SMS Transmission Failed!')
                                        ->danger()
                                        ->body($result['message'] ?? 'Check your API token and Sender ID.')
                                        ->send();
                                }
                            }),
                    ]),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Notification::make()->title('SMS settings saved successfully!')->success()->send();
    }
}
