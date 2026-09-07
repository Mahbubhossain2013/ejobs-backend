<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyReviewResource\Pages;
use App\Models\CompanyReview;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompanyReviewResource extends Resource
{
    protected static ?string $model = CompanyReview::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Users';
    protected static ?string $navigationLabel = 'Company Reviews';
    protected static ?string $slug = 'user-management/company-reviews';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Review Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->disabled()
                        ->dehydrated()
                        ->label('Reviewer (Candidate)'),
                    Forms\Components\Select::make('company_id')
                        ->relationship('company', 'name')
                        ->disabled()
                        ->dehydrated()
                        ->label('Target Company'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'approved' => 'Approved',
                            'pending' => 'Pending Review',
                            'flagged' => 'Flagged by AI',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->default('approved'),
                    Forms\Components\Toggle::make('is_anonymous')
                        ->label('Post Anonymously')
                        ->onColor('success'),
                    Forms\Components\Textarea::make('comment')
                        ->required()
                        ->columnSpanFull()
                        ->rows(4)
                        ->label('Review Comments'),
                ])->columns(2),

            Forms\Components\Section::make('Category Breakdown Ratings')
                ->schema([
                    Forms\Components\TextInput::make('rating')->numeric()->minValue(1)->maxValue(5)->label('Overall Rating'),
                    Forms\Components\TextInput::make('rating_work_culture')->numeric()->minValue(1)->maxValue(5)->label('Work Culture'),
                    Forms\Components\TextInput::make('rating_salary')->numeric()->minValue(1)->maxValue(5)->label('Salary & Perks'),
                    Forms\Components\TextInput::make('rating_management')->numeric()->minValue(1)->maxValue(5)->label('Management'),
                    Forms\Components\TextInput::make('rating_growth')->numeric()->minValue(1)->maxValue(5)->label('Career Growth'),
                    Forms\Components\TextInput::make('rating_work_life_balance')->numeric()->minValue(1)->maxValue(5)->label('Work-Life Balance'),
                ])->columns(3),

            Forms\Components\Section::make('AI Audit & Safety telemetry')
                ->schema([
                    Forms\Components\TextInput::make('ai_toxicity_score')
                        ->numeric()
                        ->disabled()
                        ->label('AI Toxicity Score (0-1)'),
                    Forms\Components\TextInput::make('ai_fake_probability')
                        ->numeric()
                        ->disabled()
                        ->label('AI Spam/Fake Probability (0-1)'),
                    Forms\Components\TextInput::make('ai_duplicate_score')
                        ->numeric()
                        ->disabled()
                        ->label('Duplicate Content Match (0-1)'),
                    Forms\Components\Textarea::make('moderation_details')
                        ->disabled()
                        ->columnSpanFull()
                        ->label('AI Moderation Reasoning'),
                ])->columns(3)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.name')
                ->label('Reviewer')
                ->searchable()
                ->sortable()
                ->weight('bold'),
            Tables\Columns\TextColumn::make('company.name')
                ->label('Target Company')
                ->searchable()
                ->sortable()
                ->color('primary'),
            Tables\Columns\TextColumn::make('rating')
                ->label('Stars')
                ->formatStateUsing(fn ($state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                ->color('warning')
                ->sortable(),
            Tables\Columns\TextColumn::make('ai_toxicity_score')
                ->label('AI Toxicity')
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state > 0.60 ? 'danger' : ($state > 0.30 ? 'warning' : 'success')),
            Tables\Columns\TextColumn::make('ai_fake_probability')
                ->label('Spam Prob')
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state > 0.70 ? 'danger' : 'gray'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'pending' => 'gray',
                    'flagged' => 'warning',
                    'rejected' => 'danger',
                }),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->dateTime('M d, Y')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'approved' => 'Approved',
                    'pending' => 'Pending Review',
                    'flagged' => 'Flagged by AI',
                    'rejected' => 'Rejected',
                ]),
        ])
        ->actions([
            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn ($record) => $record->status !== 'approved')
                ->action(function ($record) {
                    $record->update(['status' => 'approved']);
                    
                    // Recalculate average rating
                    $company = $record->company;
                    if ($company) {
                        $avg = CompanyReview::where('company_id', $company->id)
                            ->where('status', 'approved')
                            ->avg('rating') ?? 0.0;
                        $company->update(['rating' => round($avg, 2)]);
                    }
                }),
            Tables\Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn ($record) => $record->status !== 'rejected')
                ->action(function ($record) {
                    $record->update(['status' => 'rejected']);
                    
                    // Recalculate average rating
                    $company = $record->company;
                    if ($company) {
                        $avg = CompanyReview::where('company_id', $company->id)
                            ->where('status', 'approved')
                            ->avg('rating') ?? 0.0;
                        $company->update(['rating' => round($avg, 2)]);
                    }
                }),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyReviews::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_company_reviews');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_company_reviews');
    }
}
