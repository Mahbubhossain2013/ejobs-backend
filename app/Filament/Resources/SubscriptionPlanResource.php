<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionPlanResource\Pages;
use App\Filament\Resources\SubscriptionPlanResource\RelationManagers\FeatureValuesRelationManager;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Core Subscription Details')->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            )
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(SubscriptionPlan::class, 'slug', ignoreRecord: true)
                            ->maxLength(255),
                    ]),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
                
                Forms\Components\Section::make('Pricing, Term & Roles')->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('৳'),
                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'trial' => 'Trial',
                                'monthly' => 'Monthly',
                                'yearly' => 'Yearly',
                                'lifetime' => 'Lifetime',
                            ])
                            ->live(),
                        Forms\Components\Select::make('role')
                            ->label('Target User Role')
                            ->required()
                            ->options([
                                'candidate' => 'Candidate',
                                'employer' => 'Employer / Recruiter',
                            ])
                            ->default('candidate'),
                    ]),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duration (Days)')
                            ->numeric()
                            ->helperText('Leave empty for Lifetime or standard cycle defaults.')
                            ->default(null),
                        Forms\Components\TextInput::make('trial_days')
                            ->label('Trial Days')
                            ->numeric()
                            ->default(0)
                            ->visible(fn (Forms\Get $get) => $get('billing_cycle') === 'trial' || $get('trial_days') > 0),
                        Forms\Components\TextInput::make('monthly_credits')
                            ->label('Monthly Credits')
                            ->numeric()
                            ->default(0)
                            ->helperText('Number of credits assigned per month.'),
                    ]),
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Toggle::make('is_popular')
                            ->label('Popular Plan')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active status (Allows purchases)')
                            ->default(true),
                        Forms\Components\Toggle::make('is_visible')
                            ->label('Visible on Frontend')
                            ->default(true),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'candidate' => 'success',
                        'employer' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('BDT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'trial' => 'warning',
                        'monthly' => 'info',
                        'yearly' => 'success',
                        'lifetime' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} Days" : 'Lifetime')
                    ->sortable(),
                Tables\Columns\TextColumn::make('featureValuesCount')
                    ->label('Features')
                    ->counts('featureValues')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'candidate' => 'Candidate',
                        'employer' => 'Employer',
                    ]),
                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        'trial' => 'Trial',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'lifetime' => 'Lifetime',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('viewFeatures')
                    ->label('View Features')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->modalHeading(fn (SubscriptionPlan $record) => "Features — {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalContent(function (SubscriptionPlan $record) {
                        $values = $record->featureValues()->with('feature')->get();
                        $employerFeatures = $values->filter(fn ($v) => $v->feature?->role === 'employer');
                        $candidateFeatures = $values->filter(fn ($v) => $v->feature?->role === 'candidate');
                        $generalFeatures = $values->filter(fn ($v) => is_null($v->feature?->role));

                        return view('filament.resources.subscription-plan-resource.modals.features', [
                            'plan' => $record,
                            'employerFeatures' => $employerFeatures,
                            'candidateFeatures' => $candidateFeatures,
                            'generalFeatures' => $generalFeatures,
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (SubscriptionPlan $record) {
                        $copy = $record->duplicate();
                        \Filament\Notifications\Notification::make()
                            ->title('Plan Duplicated')
                            ->body("Successfully duplicated '{$record->name}' as '{$copy->name}'.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FeatureValuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_subscriptions');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_subscriptions');
    }
}
