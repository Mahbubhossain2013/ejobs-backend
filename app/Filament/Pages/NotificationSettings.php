<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Forms;

class NotificationSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.notification-channels';
    protected static ?string $title = 'Notification Channels';
    protected static ?string $slug = 'system-settings/notification-channels';
    protected static bool $shouldRegisterNavigation = false;

    public array $adminChannels = [];
    public array $candidateSettings = [];
    public array $employerSettings = [];

    public function mount(): void
    {
        // Load saved checklist states or use defaults
        $savedCandidate = Setting::where('key', 'notification_candidate_rules')->first();
        $this->candidateSettings = $savedCandidate ? json_decode($savedCandidate->value, true) : [
            'email_completed' => true,
            'email_pending' => true,
            'email_paid' => true,
            'sms_completed' => false,
            'sms_pending' => false,
            'sms_paid' => true,
        ];

        $savedEmployer = Setting::where('key', 'notification_employer_rules')->first();
        $this->employerSettings = $savedEmployer ? json_decode($savedEmployer->value, true) : [
            'email_completed' => true,
            'email_pending' => true,
            'email_paid' => true,
            'sms_completed' => true,
            'sms_pending' => false,
            'sms_paid' => true,
        ];

        $this->adminChannels = [
            [
                'id' => 1,
                'name' => 'ServerFest Payment (SMS)',
                'provider' => 'Sms',
                'events' => ['payment:completed'],
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'ServerFest Pay Notify',
                'provider' => 'Telegram',
                'events' => ['payment:completed', 'payment:pending'],
                'is_active' => true,
            ]
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_admin_notification')
                ->label('New Admin Notification')
                ->color('primary')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Channel Name*')
                        ->placeholder('e.g., Primary Email Notifications')
                        ->required(),
                    Forms\Components\Select::make('provider')
                        ->label('Notification Provider*')
                        ->options([
                            'Email' => 'Email',
                            'Sms' => 'SMS',
                            'Telegram' => 'Telegram',
                            'Slack' => 'Slack',
                        ])
                        ->required(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    Forms\Components\CheckboxList::make('events')
                        ->label('Notification Events*')
                        ->options([
                            'payment:completed' => 'Payment Completed',
                            'payment:pending' => 'Payment Pending',
                            'invoice:paid' => 'Invoice Paid',
                            'job:posted' => 'Job Posted',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->adminChannels[] = [
                        'id' => count($this->adminChannels) + 1,
                        'name' => $data['name'],
                        'provider' => $data['provider'],
                        'events' => $data['events'],
                        'is_active' => $data['is_active'],
                    ];

                    Notification::make()
                        ->title('Admin Notification Channel Added!')
                        ->success()
                        ->body("Successfully created notification channel '{$data['name']}'.")
                        ->send();
                }),
        ];
    }

    public function toggleAdminChannel(int $id): void
    {
        foreach ($this->adminChannels as &$channel) {
            if ($channel['id'] === $id) {
                $channel['is_active'] = !$channel['is_active'];
                
                Notification::make()
                    ->title('Admin Channel Status Updated!')
                    ->success()
                    ->body("{$channel['name']} status is now " . ($channel['is_active'] ? 'enabled' : 'disabled') . ".")
                    ->send();
                break;
            }
        }
    }

    public function deleteAdminChannel(int $id): void
    {
        $this->adminChannels = array_filter($this->adminChannels, fn($c) => $c['id'] !== $id);

        Notification::make()
            ->title('Channel Deleted!')
            ->success()
            ->body('Admin notification channel removed.')
            ->send();
    }

    public function saveSettings(): void
    {
        Setting::updateOrCreate(
            ['key' => 'notification_candidate_rules'],
            ['value' => json_encode($this->candidateSettings)]
        );

        Setting::updateOrCreate(
            ['key' => 'notification_employer_rules'],
            ['value' => json_encode($this->employerSettings)]
        );

        Notification::make()
            ->title('Notification rules saved!')
            ->success()
            ->body('All transactional candidate and employer rules successfully updated.')
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_notification_settings');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}
