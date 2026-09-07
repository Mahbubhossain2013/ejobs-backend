<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Services\Notification\AdminEmailService;

class CandidateResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Candidates';
    protected static ?string $slug = 'candidates';
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Users';

    // Filter query to only show Candidates
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->role('candidate');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Candidate Personal Info')
                ->schema([
                    Forms\Components\TextInput::make('name')->required(),
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
            
            Forms\Components\Section::make('Profile Details')
                ->relationship('profile')
                ->schema([
                    Forms\Components\FileUpload::make('avatar')
                        ->label('Profile Picture')
                        ->image()
                        ->directory('avatars')
                        ->imageEditor()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('current_position')
                        ->label('Profession / Title')
                        ->placeholder('e.g. Developer, Accountant, Designer'),
                    Forms\Components\TextInput::make('phone'),
                    Forms\Components\TextInput::make('city'),
                    Forms\Components\Select::make('availability_status')
                        ->options([
                            'available' => 'Available for Work',
                            'busy' => 'Busy / Working',
                            'not_looking' => 'Not Looking',
                        ])
                        ->default('available'),
                    Forms\Components\TextInput::make('experience_years')
                        ->numeric()
                        ->label('Experience (Years)'),
                    Forms\Components\TextInput::make('expected_salary')
                        ->numeric()
                        ->label('Expected Salary (Monthly)'),
                    Forms\Components\TextInput::make('portfolio_link')
                        ->url()
                        ->label('Portfolio Link'),
                    Forms\Components\Textarea::make('bio')
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('resume_path')
                        ->label('Resume (CV) [PDF Only, Max 2MB]')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(2048)
                        ->directory('resumes')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Verified')
                        ->helperText('Mark this candidate as verified')
                        ->inline(false),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table        ->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('email')->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->state(fn (User $record): string => $record->profile
                    ? ($record->profile->ban_status
                        ? 'Banned'
                        : ($record->profile->restriction_status === 'suspended'
                            ? 'Suspended'
                            : ($record->profile->restriction_status === 'limited'
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
            Tables\Columns\TextColumn::make('profile.phone')->label('Phone'),
            Tables\Columns\TextColumn::make('wallet_balance')
                ->label('Balance')
                ->state(fn ($record) => \App\Models\Wallet::where('user_id', $record->id)->value('balance') ?? 0)
                ->money('BDT')
                ->color('success'),
            Tables\Columns\TextColumn::make('profile.trust_score')
                ->label('Trust Score')
                ->badge()
                ->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                ->sortable(),
            Tables\Columns\TextColumn::make('profile.reputation_status')
                ->label('Reputation')
                ->badge()
                ->color(fn ($state) => $state === 'excellent' ? 'success' : ($state === 'good' ? 'info' : ($state === 'fair' ? 'warning' : 'danger'))),
            Tables\Columns\IconColumn::make('profile.is_verified')
                ->label('Verified')
                ->boolean()
                ->sortable(),
            Tables\Columns\TextColumn::make('profile.profile_completion_percentage')
                ->label('Completion %')
                ->numeric()
                ->suffix('%')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
        ->actions([
            Tables\Actions\Action::make('change_status')
                ->label('Change Status')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->modalHeading('Change Candidate Status')
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
                        ->default(fn (User $record) => $record->profile
                            ? ($record->profile->ban_status
                                ? 'banned'
                                : ($record->profile->restriction_status === 'suspended'
                                    ? 'suspended'
                                    : ($record->profile->restriction_status === 'limited'
                                        ? 'restricted'
                                        : 'active')))
                            : 'active'),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->rows(3),
                ])
                ->action(function (User $record, array $data) {
                    $profile = $record->profile()->firstOrCreate(['user_id' => $record->id]);
                    match ($data['status']) {
                        'active' => $profile->update([
                            'ban_status' => false,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $profile->moderation_notes,
                        ]),
                        'inactive' => $profile->update([
                            'ban_status' => false,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $profile->moderation_notes,
                        ]),
                        'suspended' => $profile->update([
                            'ban_status' => false,
                            'restriction_status' => 'suspended',
                            'moderation_notes' => $data['reason'] ?? $profile->moderation_notes,
                        ]),
                        'restricted' => $profile->update([
                            'ban_status' => false,
                            'restriction_status' => 'limited',
                            'moderation_notes' => $data['reason'] ?? $profile->moderation_notes,
                        ]),
                        'banned' => $profile->update([
                            'ban_status' => true,
                            'restriction_status' => 'active',
                            'moderation_notes' => $data['reason'] ?? $profile->moderation_notes,
                        ]),
                    };
                    FilamentNotification::make()
                        ->title('Status updated to ' . ucfirst($data['status']) . '!')
                        ->success()
                        ->send();
                }),

            Tables\Actions\EditAction::make(),
            
            Tables\Actions\Action::make('manage_wallet')
                ->label('Wallet')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->modalHeading('Manage Candidate Wallet')
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
                    $wallet = \App\Models\Wallet::firstOrCreate(['user_id' => $record->id]);
                    $amount = (float) $data['amount'];
                    
                    try {
                        if ($data['transaction_type'] === 'credit') {
                            $wallet->credit($amount, 'admin_adjustment', auth()->id(), $data['note']);
                            $message = "Your wallet has been credited with ৳{$amount}. Reason: {$data['note']}";
                        } else {
                            $wallet->debit($amount, 'admin_adjustment', auth()->id(), $data['note']);
                            $message = "Your wallet has been debited by ৳{$amount}. Reason: {$data['note']}";
                        }

                        // Real-Time Notification to Candidate React App
                        \Illuminate\Support\Facades\Notification::send($record, new \App\Notifications\SystemNotification([
                            'title' => 'Wallet Update',
                            'message' => $message,
                            'type' => 'wallet',
                            'action_url' => '/dashboard/wallet',
                        ]));

                        \Filament\Notifications\Notification::make()->title('Wallet adjusted & candidate notified!')->success()->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()->title('Transaction Failed')->body($e->getMessage())->danger()->send();
                    }
                }),
            
            Tables\Actions\Action::make('manage_trust')
                ->label('Trust')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->modalHeading('Manage Candidate Trust')
                ->form([
                    Forms\Components\TextInput::make('trust_score')
                        ->label('Trust Score (0-100)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required()
                        ->default(fn ($record) => $record->profile ? $record->profile->trust_score : 100),
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
                        ->default(fn ($record) => $record->profile ? $record->profile->reputation_status : 'good'),
                    Forms\Components\Toggle::make('is_featured')
                        ->label('Featured Visibility')
                        ->default(fn ($record) => $record->profile ? $record->profile->is_featured : false),
                ])
                ->action(function (User $record, array $data) {
                    $profile = $record->profile()->firstOrCreate(['user_id' => $record->id]);
                    $profile->update($data);
                    
                    \Filament\Notifications\Notification::make()
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
                            ->whereIn('role', ['candidate', 'both'])
                            ->pluck('name', 'id')
                        )
                        ->required(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expiration Date (Optional)'),
                ])
                ->action(function (User $record, array $data) {
                    if ($record->badges()->where('badge_id', $data['badge_id'])->exists()) {
                        \Filament\Notifications\Notification::make()
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

                    \Filament\Notifications\Notification::make()
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

                    \Filament\Notifications\Notification::make()
                        ->title('Badge revoked successfully!')
                        ->success()
                        ->send();
                }),

            Tables\Actions\Action::make('impersonate')
                ->label('Login')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Login as Candidate')
                ->modalDescription('Are you sure you want to log in as this candidate? This will log you in to their dashboard.')
                ->action(function (User $record) {
                    $token = $record->createToken('admin_sso_token')->plainTextToken;
                    $role = 'candidate';
                    $frontendUrl = config('app.frontend_url', config('app.url', 'https://ejobs.bd'));
                    $url = "{$frontendUrl}/login?sso_token={$token}&role={$role}";
                    return redirect()->away($url);
                }),

            Tables\Actions\Action::make('toggle_verified')
                ->label(fn (User $record) => $record->profile && $record->profile->is_verified ? 'Unverify' : 'Verify')
                ->icon(fn (User $record) => $record->profile && $record->profile->is_verified ? 'heroicon-o-x-circle' : 'heroicon-o-check-badge')
                ->color(fn (User $record) => $record->profile && $record->profile->is_verified ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalHeading(fn (User $record) => $record->profile && $record->profile->is_verified ? 'Unverify User' : 'Verify User')
                ->modalDescription(fn (User $record) => $record->profile && $record->profile->is_verified ? 'This will revoke the verified status.' : 'This will mark the user as verified.')
                ->action(function (User $record) {
                    $profile = $record->profile()->firstOrCreate(['user_id' => $record->id]);
                    $wasVerified = (bool) $profile->is_verified;
                    $profile->update(['is_verified' => !$wasVerified]);

                    $newStatus = $wasVerified ? 'unverified' : 'verified';

                    AdminEmailService::notifyUser(
                        $record,
                        'Your verification status has been updated',
                        'emails.verification_status_changed',
                        [
                            'userName' => $record->name,
                            'type' => 'account',
                            'status' => $newStatus,
                            'reason' => $wasVerified ? 'Your verified status has been revoked by an administrator.' : 'Your account has been verified by an administrator.',
                        ]
                    );

                    FilamentNotification::make()
                        ->title('User ' . ucfirst($newStatus))
                        ->success()
                        ->send();
                }),

            Tables\Actions\Action::make('ban')
                ->label('Ban')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Ban User')
                ->modalDescription('This user will be banned from the platform.')
                ->form([
                    Forms\Components\Textarea::make('ban_reason')
                        ->label('Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (User $record, array $data) {
                    $profile = $record->profile;
                    if (!$profile) {
                        $profile = $record->profile()->create();
                    }
                    $profile->update([
                        'ban_status' => true,
                        'moderation_notes' => $data['ban_reason'],
                    ]);

                    AdminEmailService::notifyUser(
                        $record,
                        'Your account has been banned',
                        'emails.account_status_changed',
                        [
                            'userName' => $record->name,
                            'status' => 'banned',
                            'reason' => $data['ban_reason'],
                            'supportUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/support',
                        ]
                    );

                    FilamentNotification::make()->title('User Banned')->success()->send();
                }),

            Tables\Actions\Action::make('restrict')
                ->label('Restrict')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restrict User')
                ->modalDescription('This user will have restricted access.')
                ->form([
                    Forms\Components\Select::make('restriction_type')
                        ->label('Restriction Type')
                        ->options([
                            'suspended' => 'Suspended',
                            'limited' => 'Limited Access',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('restriction_reason')
                        ->label('Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (User $record, array $data) {
                    $profile = $record->profile;
                    if (!$profile) {
                        $profile = $record->profile()->create();
                    }
                    $profile->update([
                        'restriction_status' => $data['restriction_type'],
                        'moderation_notes' => $data['restriction_reason'],
                    ]);

                    // Email restricted user
                    AdminEmailService::notifyUser(
                        $record,
                        'Your account has been ' . $data['restriction_type'],
                        'emails.account_status_changed',
                        [
                            'userName' => $record->name,
                            'status' => $data['restriction_type'],
                            'reason' => $data['restriction_reason'],
                            'supportUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/support',
                        ]
                    );

                    FilamentNotification::make()->title('User Restricted')->success()->send();
                }),

            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_candidates');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_candidates');
    }
}