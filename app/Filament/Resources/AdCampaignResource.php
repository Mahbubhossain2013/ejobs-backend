<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdCampaignResource\Pages;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Ad\AdAiModerationService;
use App\Services\Ad\AdRankingService;
use App\Services\Ad\CampaignBillingService;
use App\Services\Ad\AdAuditService;
use App\Services\Ad\AdFraudDetectionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AdCampaignResource extends Resource
{
    protected static ?string $model = Promotion::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Marketing';
    protected static ?string $navigationLabel = 'Campaign Manager';
    protected static ?string $modelLabel = 'Campaign Promotion';
    protected static ?string $pluralModelLabel = 'Campaign Promotions';
    protected static ?string $slug = 'marketing/campaigns';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Campaign Detail Configuration')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('General Information')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Employer')
                                    ->relationship('user', 'name', fn ($query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'employer')))
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('job_id')
                                    ->label('Targeted Job Circular')
                                    ->relationship('job', 'title')
                                    ->searchable()
                                    ->nullable(),
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('campaign_type')
                                    ->options([
                                        'sponsored_job' => 'Job Promotion (Sponsored Job)',
                                        'awareness' => 'Brand Awareness Campaign',
                                        'branding' => 'Branding Circular Boost',
                                        'job_boost' => 'Job Boost Boost',
                                    ])
                                    ->default('sponsored_job')
                                    ->required(),
                                Forms\Components\DatePicker::make('start_date')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\DatePicker::make('end_date')
                                    ->nullable(),
                            ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Budget & Deductions')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('daily_budget')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->required(),
                                Forms\Components\TextInput::make('total_budget')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->required(),
                                Forms\Components\TextInput::make('spent_amount')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->default(0.00)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        ]),

                    Forms\Components\Tabs\Tab::make('Ranking overrides')
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Toggle::make('is_pinned')
                                    ->label('Pin Ad to top searches')
                                    ->helperText('Override bid entirely and pin this listing to absolute top searches.'),
                                Forms\Components\Select::make('ranking_override')
                                    ->label('Rank Overriding Status')
                                    ->options([
                                        'none' => 'No Override',
                                        'boost' => 'Force Boost Priority (+5.0)',
                                        'demote' => 'Force Demote Visibility (-5.0)',
                                    ])
                                    ->default('none'),
                            ]),
                            Forms\Components\Section::make('Component Multiplier Overrides')
                                ->description('Fine-tune individual ranking engine weight parameters for this specific campaign')
                                ->schema([
                                    Forms\Components\Grid::make(4)->schema([
                                        Forms\Components\TextInput::make('relevance_override')
                                            ->numeric()
                                            ->default(1.00)
                                            ->helperText('Relevance multiplier'),
                                        Forms\Components\TextInput::make('bid_override')
                                            ->numeric()
                                            ->default(1.00)
                                            ->helperText('Bid multiplier'),
                                        Forms\Components\TextInput::make('ctr_override')
                                            ->numeric()
                                            ->default(1.00)
                                            ->helperText('CTR multiplier'),
                                        Forms\Components\TextInput::make('freshness_override')
                                            ->numeric()
                                            ->default(1.00)
                                            ->helperText('Freshness multiplier'),
                                    ]),
                                ]),
                        ]),
                    
                    Forms\Components\Tabs\Tab::make('AI Security & Fraud status')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Toggle::make('whitelisted_employer')
                                    ->label('Whitelisted (Bypasses AI checks)'),
                                Forms\Components\Toggle::make('is_suspended')
                                    ->label('Fraud Suspension Active')
                                    ->disabled(),
                                Forms\Components\Select::make('moderation_status')
                                    ->options([
                                        'safe' => 'Safe (Passed AI screening)',
                                        'uncertain' => 'Borderline Risk (Needs review)',
                                        'high_risk' => 'High Risk (Blocked)',
                                    ])
                                    ->default('safe')
                                    ->required(),
                            ]),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('spam_score')->numeric()->suffix('%')->disabled(),
                                Forms\Components\TextInput::make('fraud_score')->numeric()->suffix('%')->disabled(),
                                Forms\Components\TextInput::make('link_safety_score')->numeric()->disabled(),
                                Forms\Components\TextInput::make('content_policy_score')->numeric()->suffix('%')->disabled(),
                                Forms\Components\TextInput::make('duplicate_score')->numeric()->suffix('%')->disabled(),
                                Forms\Components\TextInput::make('anomaly_score')->numeric()->suffix('%')->disabled(),
                            ]),
                            Forms\Components\Textarea::make('ai_explanation')
                                ->label('AI screening logs & explanation')
                                ->disabled()
                                ->rows(3),
                        ]),
                ])
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')
                ->label('Employer')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('title')
                ->label('Campaign Name')
                ->searchable()
                ->wrap(),

            Tables\Columns\TextColumn::make('campaign_type')
                ->label('Type')
                ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                ->badge()
                ->colors([
                    'primary' => 'sponsored_job',
                    'success' => 'job_boost',
                    'warning' => 'awareness',
                    'danger' => 'branding',
                ]),

            Tables\Columns\TextColumn::make('daily_budget')
                ->label('Daily')
                ->money('BDT')
                ->sortable(),

            Tables\Columns\TextColumn::make('total_budget')
                ->label('Total Budget')
                ->money('BDT')
                ->sortable(),

            Tables\Columns\TextColumn::make('spent_amount')
                ->label('Spent')
                ->money('BDT')
                ->sortable(),

            Tables\Columns\TextColumn::make('remaining')
                ->label('Remaining Balance')
                ->money('BDT')
                ->state(fn (Promotion $record) => max(0, $record->total_budget - $record->spent_amount))
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'success' => 'active',
                    'warning' => 'paused',
                    'danger' => 'suspended',
                    'gray' => ['expired', 'cancelled', 'draft'],
                    'info' => 'pending_review',
                ])
                ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

            Tables\Columns\TextColumn::make('impressions')
                ->label('Imps')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('clicks')
                ->label('Clicks')
                ->numeric()
                ->sortable(),

            Tables\Columns\TextColumn::make('ctr')
                ->label('CTR %')
                ->state(function (Promotion $record) {
                    if ($record->impressions === 0) return '0.00%';
                    return number_format(($record->clicks / $record->impressions) * 100, 2) . '%';
                }),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'active' => 'Active',
                    'paused' => 'Paused',
                    'pending_review' => 'Pending Review',
                    'rejected' => 'Rejected',
                    'suspended' => 'Suspended',
                    'completed' => 'Completed',
                ]),
            Tables\Filters\SelectFilter::make('campaign_type')
                ->options([
                    'sponsored_job' => 'Job Promotion',
                    'awareness' => 'Awareness',
                    'branding' => 'Branding',
                    'job_boost' => 'Job Boost',
                ]),
            Tables\Filters\Filter::make('is_suspended')
                ->label('Fraud Suspended')
                ->query(fn (Builder $query) => $query->where('is_suspended', true)),
            Tables\Filters\Filter::make('pinned')
                ->label('Pinned Ads Only')
                ->query(fn (Builder $query) => $query->where('is_pinned', true)),
        ])
        ->actions([
            // Approve Campaign Action
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (Promotion $record) => in_array($record->status, ['pending_review', 'paused', 'rejected']))
                ->requiresConfirmation()
                ->action(function (Promotion $record) {
                    $old = $record->status;
                    
                    // Upfront Lock Budget before going live!
                    $locked = CampaignBillingService::lockUpfrontBudget($record);
                    if ($locked) {
                        $record->update([
                            'status' => 'active',
                            'rejection_reason' => null
                        ]);

                        AdAuditService::logAction('approve_campaign', $record->id, ['status' => $old], ['status' => 'active'], 'Campaign approved by admin and daily budget locked.');
                        Notification::make()->title('Campaign Approved successfully. Budget Locked.')->success()->send();
                    } else {
                        Notification::make()->title('Failed: Insufficient Wallet Balance!')->danger()->send();
                    }
                }),

            // Reject Campaign Action
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Promotion $record) => in_array($record->status, ['pending_review', 'active']))
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                ])
                ->action(function (Promotion $record, array $data) {
                    $old = $record->status;
                    
                    // Release locked budget if active
                    if ($old === 'active') {
                        CampaignBillingService::releaseLockedBudget($record);
                    }

                    $record->update([
                        'status' => 'rejected',
                        'rejection_reason' => $data['rejection_reason']
                    ]);

                    AdAuditService::logAction('reject_campaign', $record->id, ['status' => $old], ['status' => 'rejected'], $data['rejection_reason']);
                    Notification::make()->title('Campaign Rejected & Locked budget released.')->warning()->send();
                }),

            // Pause Campaign
            Action::make('pause')
                ->label('Pause')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn (Promotion $record) => $record->status === 'active')
                ->action(function (Promotion $record) {
                    $old = $record->status;
                    
                    // Release locked residual daily balance back to wallet
                    CampaignBillingService::releaseLockedBudget($record);

                    $record->update(['status' => 'paused']);

                    AdAuditService::logAction('pause_campaign', $record->id, ['status' => $old], ['status' => 'paused'], 'Admin manually paused campaign.');
                    Notification::make()->title('Campaign Paused. Locked budget released.')->success()->send();
                }),

            // Resume Campaign
            Action::make('resume')
                ->label('Resume')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn (Promotion $record) => $record->status === 'paused')
                ->action(function (Promotion $record) {
                    $old = $record->status;

                    // Re-lock daily budget lock
                    $locked = CampaignBillingService::lockUpfrontBudget($record);
                    if ($locked) {
                        $record->update(['status' => 'active']);

                        AdAuditService::logAction('resume_campaign', $record->id, ['status' => $old], ['status' => 'active'], 'Admin manually resumed campaign.');
                        Notification::make()->title('Campaign Resumed successfully. Daily budget locked.')->success()->send();
                    } else {
                        Notification::make()->title('Failed: Insufficient Wallet Balance!')->danger()->send();
                    }
                }),

            // Force Boost
            Action::make('boost')
                ->label('Force Boost')
                ->icon('heroicon-o-bolt')
                ->color('info')
                ->visible(fn (Promotion $record) => $record->status === 'active' && $record->ranking_override !== 'boost')
                ->action(function (Promotion $record) {
                    $old = $record->ranking_override;
                    $record->update(['ranking_override' => 'boost']);

                    AdAuditService::logAction('boost_override', $record->id, ['ranking_override' => $old], ['ranking_override' => 'boost'], 'Force Boost override ranking visibility.');
                    Notification::make()->title('Campaign successfully force boosted (+5.0 ranking).')->success()->send();
                }),

            // Force Demote
            Action::make('demote')
                ->label('Force Demote')
                ->icon('heroicon-o-chevron-double-down')
                ->color('gray')
                ->visible(fn (Promotion $record) => $record->status === 'active' && $record->ranking_override !== 'demote')
                ->action(function (Promotion $record) {
                    $old = $record->ranking_override;
                    $record->update(['ranking_override' => 'demote']);

                    AdAuditService::logAction('demote_override', $record->id, ['ranking_override' => $old], ['ranking_override' => 'demote'], 'Force Demote override visibility.');
                    Notification::make()->title('Campaign successfully force demoted (-5.0 ranking).')->warning()->send();
                }),

            // Terminate Campaign
            Action::make('terminate')
                ->label('Terminate')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Promotion $record) => in_array($record->status, ['active', 'paused', 'pending_review']))
                ->action(function (Promotion $record) {
                    $old = $record->status;
                    
                    // Release locked budget
                    CampaignBillingService::releaseLockedBudget($record);

                    $record->update(['status' => 'completed']);

                    AdAuditService::logAction('terminate_campaign', $record->id, ['status' => $old], ['status' => 'completed'], 'Admin forced campaign termination.');
                    Notification::make()->title('Campaign Terminated successfully.')->success()->send();
                }),

            // View Inspection Link
            Tables\Actions\ViewAction::make()->label('Inspect'),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdCampaigns::route('/'),
            'create' => Pages\CreateAdCampaign::route('/create'),
            'edit' => Pages\EditAdCampaign::route('/{record}/edit'),
            'view' => Pages\ViewAdCampaign::route('/{record}'), // Interactive inspection panel
        ];
    }
}
