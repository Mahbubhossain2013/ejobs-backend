<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RemoteJobResource\Pages;
use App\Models\Job;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RemoteJobResource extends Resource
{
    protected static ?string $model = Job::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Jobs';
    protected static ?string $navigationLabel = 'Remote Jobs';
    protected static ?string $modelLabel = 'Remote Job';
    protected static ?string $pluralModelLabel = 'Remote Jobs';
    protected static ?string $slug = 'job-management/remote-jobs';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_remote_project', true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('company_id')
                        ->relationship('company', 'name')
                        ->required(),
                    Forms\Components\Select::make('category_id')
                        ->relationship('category', 'name_en')
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state)))
                        ->required(),
                    Forms\Components\TextInput::make('slug')->required(),
                ]),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('budget_type')
                        ->options([
                            'fixed' => 'Fixed Price',
                            'hourly' => 'Hourly',
                        ])
                        ->default('fixed')
                        ->required(),
                    Forms\Components\TextInput::make('budget')
                        ->numeric()
                        ->prefix('৳')
                        ->required(),
                    Forms\Components\DatePicker::make('deadline')->required(),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Select::make('experience_level')
                        ->options([
                            'entry' => 'Entry Level',
                            'intermediate' => 'Intermediate',
                            'expert' => 'Expert',
                        ])
                        ->default('entry')
                        ->required(),
                    Forms\Components\Select::make('project_duration')
                        ->options([
                            'Less than 1 month' => 'Less than 1 month',
                            '1 to 3 months' => '1 to 3 months',
                            '3 to 6 months' => '3 to 6 months',
                            'More than 6 months' => 'More than 6 months',
                        ])
                        ->default('Less than 1 month')
                        ->required(),
                    Forms\Components\TextInput::make('timezone')
                        ->placeholder('e.g. GMT+6')
                        ->default('Global')
                        ->required(),
                ]),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\TextInput::make('country_restriction')
                        ->placeholder('e.g. None')
                        ->default('None')
                        ->required(),
                    Forms\Components\TextInput::make('language_requirement')
                        ->placeholder('e.g. English')
                        ->default('English')
                        ->required(),
                    Forms\Components\TextInput::make('location')
                        ->default('Remote')
                        ->required(),
                ]),
                Forms\Components\TagsInput::make('required_skills')
                    ->separator(',')
                    ->placeholder('React, PHP')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->searchable(),
            Tables\Columns\TextColumn::make('company.name')->label('Company'),
            Tables\Columns\TextColumn::make('budget')
                ->money('BDT')
                ->sortable(),
            Tables\Columns\TextColumn::make('budget_type')->label('Budget Type'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            Tables\Columns\TextColumn::make('deadline')
                ->date()
                ->sortable(),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemoteJobs::route('/'),
            'create' => Pages\CreateRemoteJob::route('/create'),
            'edit' => Pages\EditRemoteJob::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_remote_jobs');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_remote_jobs');
    }
}
