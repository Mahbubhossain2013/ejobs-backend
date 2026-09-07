<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxSettingResource\Pages;
use App\Models\TaxSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TaxSettingResource extends Resource
{
    protected static ?string $model           = TaxSetting::class;
    protected static ?string $navigationIcon  = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Tax Settings';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int    $navigationSort  = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tax Rule')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Rule Name')
                        ->required()
                        ->placeholder('e.g. Global VAT, UAE VAT'),

                    Forms\Components\TextInput::make('label')
                        ->label('Display Label')
                        ->default('VAT')
                        ->placeholder('Shown on invoice as: VAT, GST, Tax'),

                    Forms\Components\TextInput::make('rate')
                        ->label('Rate (%)')
                        ->numeric()
                        ->required()
                        ->suffix('%')
                        ->default(0),

                    Forms\Components\TextInput::make('country_code')
                        ->label('Country Code (ISO)')
                        ->placeholder('e.g. US, AE, IN — leave blank for global')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('region')
                        ->label('State / Region')
                        ->placeholder('Optional (e.g. CA for California)'),

                    Forms\Components\Select::make('applies_to')
                        ->label('Applies To')
                        ->options([
                            'all'          => 'All Invoice Types',
                            'subscription' => 'Subscriptions Only',
                            'job_boost'    => 'Job Boosts Only',
                            'milestone'    => 'Milestones Only',
                        ])
                        ->default('all'),

                    Forms\Components\Toggle::make('is_inclusive')->label('Tax Inclusive (tax included in price)'),
                    Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                    Forms\Components\Toggle::make('is_default')->label('Set as Default Global Rule'),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Label'),
                Tables\Columns\TextColumn::make('rate')->label('Rate')->suffix('%')->sortable(),
                Tables\Columns\TextColumn::make('country_code')->label('Country')->placeholder('Global'),
                Tables\Columns\TextColumn::make('applies_to')->label('Applies To')->badge(),
                Tables\Columns\IconColumn::make('is_inclusive')->label('Inclusive')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->visible(fn($record) => !$record->is_default)
                    ->action(function ($record) {
                        $record->setAsDefault();
                        Notification::make()->title('Tax rule set as default.')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTaxSettings::route('/'),
            'create' => Pages\CreateTaxSetting::route('/create'),
            'edit'   => Pages\EditTaxSetting::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_tax_settings');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_tax_settings');
    }
}
