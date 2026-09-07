<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployerResource\Pages;
use App\Models\User;
use App\Models\Company;
use App\Models\Wallet;
use App\Models\Job;
use App\Models\Promotion;
use App\Notifications\SystemNotification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use Filament\Notifications\Notification;

class EmployerResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Employers';
    protected static ?string $slug = 'employers';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Users';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('employer');
    }

    // --- 1. EDIT & CREATE FORM ---
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Information')->schema([
                Forms\Components\TextInput::make('name')->label('Contact Name')->required(),
                Forms\Components\TextInput::make('username')
                    ->unique(ignoreRecord: true)
                    ->required(),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->label('Change Password')
                    ->helperText('Leave blank to keep the current password.'),
            ])->columns(2),

            Forms\Components\Section::make('Company Details & Verification')
                ->relationship('company') 
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Company Name')->required(),
                    Forms\Components\TextInput::make('industry'),
                    Forms\Components\TextInput::make('website')
                        ->url()
                        ->label('Company Website'),
                    Forms\Components\TextInput::make('founded_year')
                        ->numeric()
                        ->label('Founded Year'),
                    Forms\Components\TextInput::make('size')
                        ->placeholder('e.g. 1-10, 11-50, 100-500')
                        ->label('Company Size'),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\TextInput::make('trade_license_number')
                        ->label('Trade License Number'),
                    Forms\Components\FileUpload::make('trade_license_document')
                        ->label('Trade License Document')
                        ->directory('licenses')
                        ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/*']),
                    Forms\Components\FileUpload::make('logo')
                        ->label('Company Logo')
                        ->directory('logos')
                        ->image()
                        ->avatar(),
                    Forms\Components\Textarea::make('description')
                        ->label('Company Description / Bio')
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Verify Employer')
                        ->onColor('success')
                        ->helperText('Enable this to allow employer to post jobs.')
                        ->afterStateUpdated(function ($state, $old, $set, $record) {
                            if ($state == $old || !$record) return;
                            try {
                                $company = $record instanceof Company ? $record : $record->company;
                                if (!$company) return;
                                $employer = $company->owner ?? \App\Models\User::find($company->user_id);
                                if (!$employer || !$employer->email) return;

                                $brandName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                                $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/employer/dashboard';

                                if ($state) {
                                    Mail::send('emails.employer_verified', [
                                        'brandName' => $brandName,
                                        'employerName' => $employer->name,
                                        'companyName' => $company->name,
                                        'dashboardUrl' => $dashboardUrl,
                                    ], function ($mail) use ($employer, $brandName) {
                                        $mail->to($employer->email)
                                             ->subject("{$brandName} — Your company has been verified!");
                                });
                                } else {
                                    Mail::send('emails.account_status_changed', [
                                        'brandName' => $brandName,
                                        'userName' => $employer->name,
                                        'status' => 'unverified',
                                        'reason' => 'Your company verification has been revoked. Please contact support for more information.',
                                        'supportUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/support',
                                    ], function ($mail) use ($employer, $brandName) {
                                        $mail->to($employer->email)
                                             ->subject("{$brandName} — Your company verification has been revoked");
                                    });
                                }
                            } catch (\Throwable $e) {
                                Log::error("Employer verification email failed: " . $e->getMessage());
                            }
                        }),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Feature on Homepage')
                        ->onColor('warning'),
                ])->columns(2)
        ]);
    }

    // --- 2. VIEW DETAILS (INFOLIST) ---
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Employer & Company Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('company.name')->label('Company Name')->weight('bold')->color('primary'),
                        Infolists\Components\TextEntry::make('name')->label('HR Contact'),
                        Infolists\Components\TextEntry::make('email')->label('Account Email'),
                        Infolists\Components\TextEntry::make('company.industry')->label('Industry'),
                        Infolists\Components\TextEntry::make('company.location')->label('Headquarters'),
                        Infolists\Components\IconEntry::make('company.is_verified')->label('Verification Status')->boolean(),
                    ])->columns(3),

                Infolists\Components\Section::make('Financial & Activity Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('wallet_balance')
                            ->label('Available Wallet Balance')
                            ->state(fn ($record) => Wallet::where('user_id', $record->id)->value('balance') ?? 0)
                            ->money('BDT')
                            ->weight('black')
                            ->color('success'),
                            
                        Infolists\Components\TextEntry::make('escrow_balance')
                            ->label('Locked Escrow Funds')
                            ->state(fn ($record) => Wallet::where('user_id', $record->id)->value('locked_balance') ?? 0)
                            ->money('BDT')
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('total_jobs')
                            ->label('Jobs Posted')
                            ->state(function ($record) {
                                $companyId = Company::where('user_id', $record->id)->value('id');
                                return $companyId ? Job::where('company_id', $companyId)->count() : 0;
                            })
                            ->badge(),

                        Infolists\Components\TextEntry::make('active_promotions')
                            ->label('Active Campaigns')
                            ->state(fn ($record) => Promotion::where('user_id', $record->id)->where('status', 'active')->count())
                            ->badge()
                            ->color('info'),
                    ])->columns(4),
            ]);
    }

    // --- 3. DATA TABLE ---
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('name')->label('Contact')->searchable(),
            Tables\Columns\TextColumn::make('wallet_balance')
                ->label('Balance')
                ->state(fn ($record) => Wallet::where('user_id', $record->id)->value('balance') ?? 0)
                ->money('BDT')
                ->color('success'),
            Tables\Columns\TextColumn::make('company.trust_score')
                ->label('Trust Score')
                ->badge()
                ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                ->sortable(),
            Tables\Columns\TextColumn::make('company.reputation_status')
                ->label('Reputation')
                ->badge()
                ->color(fn ($state) => $state === 'excellent' ? 'success' : ($state === 'good' ? 'info' : ($state === 'fair' ? 'warning' : 'danger'))),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->state(fn (User $record): string => $record->company
                    ? ($record->company->ban_status
                        ? 'Banned'
                        : ($record->company->restriction_status === 'suspended'
                            ? 'Suspended'
                            : ($record->company->restriction_status === 'limited'
                                ? 'Restricted'
                                : 'Active')))
                    : 'Active')
                ->colors([
                    'success' => fn ($state) => $state === 'Active',
                    'warning' => fn ($state) => in_array($state, ['Suspended', 'Restricted']),
                    'danger' => fn ($state) => $state === 'Banned',
                ])
                ->icon(fn ($state) => match ($state) {
                    'Active' => 'heroicon-o-check-circle',
                    'Suspended' => 'heroicon-o-pause-circle',
                    'Restricted' => 'heroicon-o-lock-closed',
                    'Banned' => 'heroicon-o-no-symbol',
                    default => 'heroicon-o-question-mark-circle',
                }),
            Tables\Columns\IconColumn::make('company.is_verified')->label('Verified')->boolean(),
        ])
        ->actions([
            // 1. VIEW BUTTON (Opens Slide-over Modal)
            Tables\Actions\ViewAction::make()->slideOver(),
            
            // 2. EDIT BUTTON
            Tables\Actions\EditAction::make(),

            Tables\Actions\Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Change Employer Status')
                ->modalWidth('sm')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('New Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'suspended' => 'Suspended',
                            'restricted' => 'Restricted',
                            'banned' => 'Banned',
                        ])
                        ->required()
                        ->default(fn (User $record) => $record->company
                            ? ($record->company->ban_status
                                ? 'banned'
                                : ($record->company->restriction_status === 'suspended'
                                    ? 'suspended'
                                    : ($record->company->restriction_status === 'limited'
                                        ? 'restricted'
                                        : 'active')))
                            : 'active'),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->rows(3),
                ])
                ->action(function (User $record, array $data) {
                    $company = $record->company()->firstOrCreate(['user_id' => $record->id]);
                    match ($data['status']) {
                        'active' => $company->update([
                            'ban_status' => false,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $company->moderation_notes,
                        ]),
                        'inactive' => $company->update([
                            'ban_status' => false,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $company->moderation_notes,
                        ]),
                        'suspended' => $company->update([
                            'ban_status' => false,
                            'restriction_status' => 'suspended',
                            'moderation_notes' => $data['reason'] ?? $company->moderation_notes,
                        ]),
                        'restricted' => $company->update([
                            'ban_status' => false,
                            'restriction_status' => 'limited',
                            'moderation_notes' => $data['reason'] ?? $company->moderation_notes,
                        ]),
                        'banned' => $company->update([
                            'ban_status' => true,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $company->moderation_notes,
                        ]),
                    };
                    Notification::make()
                        ->title('Status updated to ' . ucfirst($data['status']) . '!')
                        ->success()
                        ->send();
                }),

            // 3. SECURE WALLET CONTROL (UPDATED)
            Tables\Actions\Action::make('manage_wallet')
                ->label('Wallet')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->modalHeading('Manage Employer Wallet')
                ->form([
                    Forms\Components\Select::make('transaction_type')
                        ->label('Action')
                        ->options([
                            'credit' => 'Add Funds (Credit)',
                            'debit' => 'Deduct Funds (Debit)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount (BDT)')
                        ->numeric()
                        ->minValue(1)
                        ->required(),
                    Forms\Components\Textarea::make('note')
                        ->label('Reason / Note')
                        ->required(),
                ])
                ->action(function (User $record, array $data) {
                    // Find the wallet
                    $wallet = Wallet::firstOrCreate(['user_id' => $record->id]);
                    $amount = (float) $data['amount'];
                    
                    try {
                        if ($data['transaction_type'] === 'credit') {
                            $wallet->credit($amount, 'admin_adjustment', auth()->id(), $data['note']);
                            $message = "Your wallet has been credited with ৳{$amount}. Reason: {$data['note']}";
                        } else {
                            $wallet->debit($amount, 'admin_adjustment', auth()->id(), $data['note']);
                            $message = "Your wallet has been debited by ৳{$amount}. Reason: {$data['note']}";
                        }

                        // Real-Time Notification to Employer React App
                        NotificationFacade::send($record, new SystemNotification([
                            'title' => 'Wallet Update',
                            'message' => $message,
                            'type' => 'wallet',
                            'action_url' => '/employer/wallet',
                        ]));

                        Notification::make()->title('Wallet adjusted & employer notified!')->success()->send();

                    } catch (\Exception $e) {
                        Notification::make()->title('Transaction Failed')->body($e->getMessage())->danger()->send();
                    }
                }),

            Tables\Actions\Action::make('manage_trust')
                ->label('Trust')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->modalHeading('Manage Employer Trust')
                ->form([
                    Forms\Components\TextInput::make('trust_score')
                        ->label('Trust Score (0-100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required()
                        ->default(fn ($record) => $record->company ? $record->company->trust_score : 100),
                    Forms\Components\Select::make('reputation_status')
                        ->label('Reputation Status')
                        ->options([
                            'excellent' => 'Excellent',
                            'good' => 'Good',
                            'fair' => 'Fair',
                            'suspicious' => 'Suspicious',
                            'under_review' => 'Under Review',
                        ])
                        ->required()
                        ->default(fn ($record) => $record->company ? $record->company->reputation_status : 'good'),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Visibility')
                        ->default(fn ($record) => $record->company ? $record->company->is_featured : false),
                ])
                ->action(function (User $record, array $data) {
                    $company = $record->company()->firstOrCreate(['user_id' => $record->id]);
                    $company->update($data);
                    
                    Notification::make()
                        ->title('Trust scorecard updated!')
                        ->success()
                        ->send();
                }),

            Tables\Actions\Action::make('award_badge')
                ->label('Award')
                ->icon('heroicon-o-academic-cap')
                ->color('success')
                ->modalHeading('Manually Award Badge')
                ->form([
                    Forms\Components\Select::make('badge_id')
                        ->label('Select Badge')
                        ->options(fn () => \App\Models\Badge::where('is_active', true)
                            ->whereIn('role', ['employer', 'both'])
                            ->pluck('name', 'id')
                        )
                        ->required(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expiration Date (Optional)'),
                ])
                ->action(function (User $record, array $data) {
                    if ($record->badges()->where('badge_id', $data['badge_id'])->exists()) {
                        Notification::make()
                            ->title('User already has this badge!')
                            ->danger()
                            ->send();
                        return;
                    }

                    $record->badges()->attach($data['badge_id'], [
                        'assigned_by' => 'admin',
                        'expires_at' => $data['expires_at'],
                        'is_visible' => true,
                    ]);

                    Notification::make()
                        ->title('Badge awarded successfully!')
                        ->success()
                        ->send();
                }),

            Tables\Actions\Action::make('revoke_badge')
                ->label('Revoke')
                ->icon('heroicon-o-minus-circle')
                ->color('danger')
                ->modalHeading('Revoke User Badge')
                ->form([
                    Forms\Components\Select::make('badge_id')
                        ->label('Select Badge to Revoke')
                        ->options(fn ($record) => $record->badges->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (User $record, array $data) {
                    $record->badges()->detach($data['badge_id']);

                    Notification::make()
                        ->title('Badge revoked successfully!')
                        ->success()
                        ->send();
                }),
            Tables\Actions\Action::make('impersonate')
                ->label('Login')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Login as Employer')
                ->modalDescription('Are you sure you want to log in as this employer? This will log you in to their dashboard.')
                ->action(function (User $record) {
                    $token = $record->createToken('admin_sso_token')->plainTextToken;
                    $role = 'employer';
                    $frontendUrl = config('app.frontend_url', config('app.url', 'https://ejobs.bd'));
                    $url = "{$frontendUrl}/login?sso_token={$token}&role={$role}";
                    return redirect()->away($url);
                }),

        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployers::route('/'),
            'create' => Pages\CreateEmployer::route('/create'),
            'edit' => Pages\EditEmployer::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_employers');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_employers');
    }
}