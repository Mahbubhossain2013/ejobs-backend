<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutGatewayResource\Pages;
use App\Models\PayoutGateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PayoutGatewayResource extends Resource
{
    protected static ?string $model = PayoutGateway::class;

    // --- 1. RENAME THE PAGE HERE ---
    protected static ?string $navigationLabel = 'Payout Manager';
    protected static ?string $pluralLabel = 'Payout Manager';
    protected static ?string $modelLabel = 'Payout Method';
    
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    
    // --- 2. ADD TO FINANCIAL GROUP ---
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Gateway Configuration')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Method Name')
                        ->placeholder('e.g. bKash Personal')
                        ->required(),
                    Forms\Components\FileUpload::make('logo')
                        ->label('Method Logo')
                        ->directory('payout-gateways')
                        ->image()
                        ->imageEditor()
                        ->maxSize(2048)
                        ->helperText('Upload bKash, Nagad, Rocket logo (max 2MB)'),
                    Forms\Components\TextInput::make('min_amount')
                        ->label('Minimum Withdrawal')
                        ->numeric()
                        ->prefix('৳')
                        ->default(500),
                    Forms\Components\TextInput::make('percent_charge')
                        ->label('Service Charge (%)')
                        ->numeric()
                        ->prefix('%')
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Enable this method')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Required Fields')
                ->description('Define what information the candidate must provide (e.g. Number, Bank Name)')
                ->schema([
                    Forms\Components\Repeater::make('user_input')
                        ->label('Custom Input Fields')
                        ->schema([
                            Forms\Components\TextInput::make('label')->required()->placeholder('Field Label'),
                            Forms\Components\TextInput::make('name')->required()->placeholder('database_key'),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'text' => 'Text',
                                    'number' => 'Number',
                                ])->required(),
                        ])->columns(3)
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->label('Logo')->circular()->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=0D8ABC&color=fff'),
                Tables\Columns\TextColumn::make('name')->fontFamily('mono')->weight('bold'),
                Tables\Columns\TextColumn::make('min_amount')->money('BDT'),
                Tables\Columns\TextColumn::make('percent_charge')->label('Charge')->suffix('%'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutGateways::route('/'),
            'create' => Pages\CreatePayoutGateway::route('/create'),
            'edit' => Pages\EditPayoutGateway::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_payout_gateways');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_payout_gateways');
    }
}