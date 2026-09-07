<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class MailSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.mail-settings';
    protected static ?string $title = 'Mail Settings';
    protected static ?string $slug = 'system-settings/mail-settings';
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
            Forms\Components\Section::make('Global Mail Settings')
                ->description('Configure system-wide email delivery. These settings will be used by all brands unless brand overrides are enabled.')
                ->schema([
                    Forms\Components\Toggle::make('mail_enabled')
                        ->label('Enable Email Sending')
                        ->helperText('Master switch for all outgoing emails.'),
                    
                    Forms\Components\Select::make('mail_driver')
                        ->label('Mail Driver*')
                        ->options(['smtp' => 'SMTP'])
                        ->default('smtp')
                        ->required()
                        ->helperText('Choose your preferred mail delivery method'),

                    Forms\Components\TextInput::make('smtp_host')
                        ->label('SMTP Host*')
                        ->placeholder('mail.spacemail.com')
                        ->required()
                        ->helperText('Enter a valid hostname (e.g., smtp.example.com)'),

                    Forms\Components\TextInput::make('smtp_port')
                        ->label('SMTP Port*')
                        ->numeric()
                        ->placeholder('465')
                        ->required()
                        ->helperText('Common ports: 25, 465 (SSL), 587 (TLS), 2525'),

                    Forms\Components\TextInput::make('smtp_username')
                        ->label('SMTP Username')
                        ->placeholder('noreply@serverfest.com')
                        ->helperText('Usually your email address'),

                    Forms\Components\TextInput::make('smtp_password')
                        ->label('SMTP Password')
                        ->password()
                        ->helperText('Use your SMTP password'),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('test_email')
                            ->label('Test Connection')
                            ->color('info')
                            ->icon('heroicon-o-arrow-path')
                            ->form([
                                Forms\Components\TextInput::make('test_email')
                                    ->label('Recipient Email')
                                    ->email()
                                    ->required()
                                    ->default(fn() => $this->data['support_email'] ?? ''),
                            ])
                            ->action(function (array $data) {
                                $recipient = $data['test_email'];
                                $settings = $this->data;

                                if (empty($settings['mail_enabled'])) {
                                    Notification::make()
                                        ->title('Mail is Disabled!')
                                        ->danger()
                                        ->body('Please check "Mail Enabled" toggle, save changes, and try again.')
                                        ->send();
                                    return;
                                }

                                $originalTimeout = ini_get('default_socket_timeout');
                                ini_set('default_socket_timeout', 5);
                                try {
                                    config([
                                        'mail.default' => 'smtp',
                                        'mail.mailers.smtp.host' => $settings['smtp_host'] ?? config('mail.mailers.smtp.host'),
                                        'mail.mailers.smtp.port' => $settings['smtp_port'] ?? config('mail.mailers.smtp.port'),
                                        'mail.mailers.smtp.username' => $settings['smtp_username'] ?? config('mail.mailers.smtp.username'),
                                        'mail.mailers.smtp.password' => $settings['smtp_password'] ?? config('mail.mailers.smtp.password'),
                                        'mail.mailers.smtp.scheme' => (($settings['smtp_port'] ?? 587) == 465) ? 'smtps' : null,
                                        'mail.mailers.smtp.timeout' => 5,
                                        'mail.from.address' => $settings['support_email'] ?? config('mail.from.address'),
                                        'mail.from.name' => $settings['site_name'] ?? config('mail.from.name'),
                                        'mail.mailers.smtp.stream' => [
                                            'ssl' => [
                                                'allow_self_signed' => true,
                                                'verify_peer' => false,
                                                'verify_peer_name' => false,
                                            ],
                                        ],
                                    ]);

                                    Mail::raw('This is a test email sent from the Job Portal Platform Settings panel to check your SMTP configuration.', function ($message) use ($recipient, $settings) {
                                        $message->to($recipient)
                                            ->subject(($settings['site_name'] ?? 'Job Portal') . ' - SMTP Configuration Test');
                                    });

                                    Notification::make()
                                        ->title('Test Email Sent!')
                                        ->success()
                                        ->body("A test email has been successfully sent to {$recipient}.")
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Mail Sending Failed!')
                                        ->danger()
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                } finally {
                                    ini_set('default_socket_timeout', $originalTimeout);
                                }
                            }),
                    ]),
                ]),

            Forms\Components\Section::make('Sender Information')
                ->description('Global sender details for outgoing emails')
                ->schema([
                    Forms\Components\TextInput::make('support_email')
                        ->label('From Address*')
                        ->email()
                        ->required()
                        ->helperText('Default outgoing email sender address'),
                    
                    Forms\Components\TextInput::make('site_name')
                        ->label('From Name*')
                        ->required()
                        ->helperText('Default outgoing email sender name'),
                ]),
        ])->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $storedValue = is_array($value) ? json_encode($value) : $value;
            Setting::updateOrCreate(['key' => $key], ['value' => $storedValue]);
        }

        Notification::make()->title('Mail settings saved successfully!')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_mail_settings');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}
