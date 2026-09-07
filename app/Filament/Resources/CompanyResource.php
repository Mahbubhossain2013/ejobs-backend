<?php

namespace App\Filament\Resources;

use App\Filament\Columns\CompanyLogoColumn;
use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Users';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Company Details')->schema([
                Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->directory('company-logos')
                    ->maxSize(2048)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('industry'),
                Forms\Components\TextInput::make('location'),
                Forms\Components\TextInput::make('website')->url(),
            ])->columns(2),

            Forms\Components\Section::make('Status & Visibility')->schema([
                Forms\Components\Toggle::make('is_verified')
                    ->label('Verified Company')
                    ->onColor('success') // FIXED: Using onColor instead of color
                    ->helperText('Allow this company to post jobs.'),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured Company')
                    ->onColor('warning') // FIXED: Using onColor instead of color
                    ->helperText('Display this company on the Homepage.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            CompanyLogoColumn::make('website')
                ->label('Logo')
                ->size(40)
                ->tooltip(fn ($record) => $record->name)
                ->url(fn ($record) => $record->website, shouldOpenInNewTab: true)
                ->fallback('monogram'),
            Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
            Tables\Columns\TextColumn::make('website')
                ->label('Website URL')
                ->url(fn ($record) => $record->website, shouldOpenInNewTab: true)
                ->color('primary')
                ->icon('heroicon-m-link')
                ->openUrlInNewTab()
                ->searchable(),
            Tables\Columns\TextColumn::make('industry')->searchable(),
            Tables\Columns\IconColumn::make('is_verified')->boolean()->label('Verified'),
            Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured')->color('warning'), // .color() is valid for Table Columns!
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_companies');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_companies');
    }
}