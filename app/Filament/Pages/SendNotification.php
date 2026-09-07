<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Models\AdminNotificationLog;
use App\Notifications\SystemNotification;
use App\Services\Notification\SmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendNotification extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Push Notifications';
    protected static ?string $title = 'Send Custom Notification';
    protected static string $view = 'filament.pages.send-notification';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'target_audience' => 'all',
            'send_database' => true,
            'send_email' => false,
            'send_sms' => false,
        ]);
    }

    // --- FORM FOR SENDING ---
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Compose Notification')->schema([
                Forms\Components\Select::make('target_audience')
                    ->options([
                        'all' => 'All Users',
                        'candidates' => 'Candidates Only',
                        'employers' => 'Employers Only',
                    ])->required()->default('all'),

                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\Textarea::make('message')->required()->rows(3),
                Forms\Components\TextInput::make('action_url')->url()->label('Action URL (Optional)'),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Toggle::make('send_database')
                        ->label('Dashboard Notification')
                        ->default(true)
                        ->required(),
                    Forms\Components\Toggle::make('send_email')
                        ->label('Email Notification')
                        ->default(false)
                        ->required(),
                ]),
            ]),

            Forms\Components\Section::make('SMS Notification')->schema([
                Forms\Components\Toggle::make('send_sms')
                    ->label('Send SMS Notification')
                    ->default(false)
                    ->reactive(),
                Forms\Components\Textarea::make('sms_message')
                    ->label('SMS Message')
                    ->rows(3)
                    ->maxLength(160)
                    ->helperText(fn (Forms\Get $get) => 'Characters: ' . strlen($get('sms_message') ?? '') . '/160')
                    ->visible(fn (Forms\Get $get) => (bool) $get('send_sms'))
                    ->required(fn (Forms\Get $get) => (bool) $get('send_sms')),
            ]),
        ])->statePath('data');
    }

    public function sendNotification(): void
    {
        try {
            $data = $this->form->getState();
            $query = User::query();

            if ($data['target_audience'] === 'candidates') $query->role('candidate');
            elseif ($data['target_audience'] === 'employers') $query->role('employer');

            $users = $query->get();

            if ($users->isEmpty()) {
                FilamentNotification::make()->title('No users found.')->warning()->send();
                return;
            }

            // Build channels list
            $channels = [];
            if ($data['send_database'] ?? false) {
                $channels[] = 'database';
            }
            if ($data['send_email'] ?? false) {
                $channels[] = 'mail';
            }
            $channels[] = 'broadcast'; // always broadcast

            // Send push/email/database notifications
            NotificationFacade::send($users, new SystemNotification([
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => 'custom_alert',
                'action_url' => $data['action_url'] ?? '/dashboard',
                'channels' => $channels,
            ]));

            // Send SMS if enabled
            $smsSent = 0;
            $smsFailed = 0;
            if (($data['send_sms'] ?? false) && !empty($data['sms_message'])) {
                $smsService = app(SmsService::class);
                foreach ($users as $user) {
                    $phone = $user->phone ?? $user->profile?->phone ?? null;
                    if ($phone) {
                        $result = $smsService->sendSms($phone, $data['sms_message']);
                        if (($result['status'] ?? '') === 'success') {
                            $smsSent++;
                        } else {
                            $smsFailed++;
                        }
                    } else {
                        $smsFailed++;
                    }
                }
                $channels[] = 'sms';
            }

            // Save to History Log
            AdminNotificationLog::create([
                'title' => $data['title'],
                'message' => $data['message'],
                'target_audience' => $data['target_audience'],
                'sent_count' => $users->count(),
                'status' => 'sent',
                'channels' => $channels,
                'action_url' => $data['action_url'] ?? null,
            ]);

            $body = "Successfully sent to {$users->count()} user(s)";
            if ($smsSent > 0) {
                $body .= ". SMS: {$smsSent} sent" . ($smsFailed > 0 ? ", {$smsFailed} failed" : "");
            }

            FilamentNotification::make()
                ->title('Notification Sent!')
                ->body($body)
                ->success()
                ->send();

            $this->form->fill([
                'target_audience' => 'all',
                'send_database' => true,
                'send_email' => false,
                'send_sms' => false,
            ]);
        } catch (\Exception $e) {
            FilamentNotification::make()
                ->title('Error Sending Notification')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // --- TABLE FOR HISTORY ---
    public function table(Table $table): Table
    {
        return $table
            ->query(AdminNotificationLog::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Sent On')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->message),
                Tables\Columns\TextColumn::make('target_audience')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'primary',
                        'candidates' => 'info',
                        'employers' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Reached')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('channels')
                    ->label('Channels')
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Again Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (AdminNotificationLog $record) {
                        try {
                            $query = User::query();
                            if ($record->target_audience === 'candidates') $query->role('candidate');
                            elseif ($record->target_audience === 'employers') $query->role('employer');

                            $users = $query->get();

                            if ($users->isEmpty()) {
                                FilamentNotification::make()->title('No users found for this audience.')->warning()->send();
                                return;
                            }

                            NotificationFacade::send($users, new SystemNotification([
                                'title' => $record->title,
                                'message' => $record->message,
                                'type' => 'custom_alert',
                                'action_url' => $record->action_url ?? '/dashboard',
                            ]));

                            AdminNotificationLog::create([
                                'title' => '[Resend] ' . $record->title,
                                'message' => $record->message,
                                'target_audience' => $record->target_audience,
                                'sent_count' => $users->count(),
                                'status' => 'sent',
                                'channels' => $record->channels ?? ['database', 'broadcast'],
                                'action_url' => $record->action_url,
                            ]);

                            FilamentNotification::make()
                                ->title('Notification Resent!')
                                ->body("Successfully resent to {$users->count()} user(s)")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            FilamentNotification::make()
                                ->title('Error Resending Notification')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
