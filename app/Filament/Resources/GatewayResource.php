<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GatewayResource\Pages;
use App\Models\Gateway;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class GatewayResource extends Resource
{
    protected static ?string $model = Gateway::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Gateway Configuration')->schema([
                Forms\Components\Select::make('drive_type')
                    ->label('Gateway Type')
                    ->options([
                        'automation' => 'Automation (API Key)',
                        'merchant' => 'Merchant (bKash/Nagad/Rocket)',
                        'personal' => 'Personal (Manual)',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('name')
                    ->label('Gateway Driver')
                    ->options([
                        'OniPay' => 'Oni Pay',
                        'bKash' => 'bKash',
                        'Nagad' => 'Nagad',
                        'Rocket' => 'Rocket',
                        'SSLCommerz' => 'SSLCommerz',
                        'EPS' => 'EPS',
                        'BkashPersonal' => 'Bkash Personal',
                        'NagadPersonal' => 'Nagad Personal',
                        'RocketPersonal' => 'Rocket Personal',
                    ])
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('display_name')
                    ->label('Display Name')
                    ->required(),

                Forms\Components\FileUpload::make('logo')
                    ->label('Gateway Logo')
                    ->directory('gateways')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->helperText('Upload bKash, Nagad, Rocket or gateway logo (max 2MB)'),

                // Automation: Single API Key
                Forms\Components\TextInput::make('api_key')
                    ->label('API Key')
                    ->password()
                    ->visible(fn ($get) => $get('drive_type') === 'automation')
                    ->required(fn ($get) => $get('drive_type') === 'automation'),

                // bKash Merchant Credentials
                Forms\Components\Section::make('bKash Credentials')
                    ->visible(fn ($get) => $get('drive_type') === 'merchant' && $get('name') === 'bKash')
                    ->schema([
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->required(),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(),
                        Forms\Components\TextInput::make('app_key')
                            ->label('App Key')
                            ->required(),
                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->password()
                            ->required(),
                    ]),

                // Nagad Merchant Credentials
                Forms\Components\Section::make('Nagad Credentials')
                    ->visible(fn ($get) => $get('drive_type') === 'merchant' && $get('name') === 'Nagad')
                    ->schema([
                        Forms\Components\TextInput::make('nagad_merchant_id')
                            ->label('Merchant ID')
                            ->required(),
                        Forms\Components\Textarea::make('nagad_private_key')
                            ->label('Merchant Private Key')
                            ->rows(3)
                            ->required(),
                        Forms\Components\Textarea::make('nagad_pg_public_key')
                            ->label('PG Public Key')
                            ->rows(3)
                            ->required(),
                    ]),

                // Rocket Merchant Credentials
                Forms\Components\Section::make('Rocket Credentials')
                    ->visible(fn ($get) => $get('drive_type') === 'merchant' && $get('name') === 'Rocket')
                    ->schema([
                        Forms\Components\TextInput::make('rocket_merchant_id')
                            ->label('Merchant ID')
                            ->required(),
                        Forms\Components\TextInput::make('rocket_api_password')
                            ->label('API Password')
                            ->password()
                            ->required(),
                        Forms\Components\TextInput::make('rocket_api_key')
                            ->label('API Key')
                            ->password()
                            ->required(),
                    ]),

                // SSLCommerz Credentials
                Forms\Components\Section::make('SSLCommerz Credentials')
                    ->visible(fn ($get) => $get('drive_type') === 'merchant' && $get('name') === 'SSLCommerz')
                    ->schema([
                        Forms\Components\TextInput::make('sslc_store_id')
                            ->label('Store ID')
                            ->required(),
                        Forms\Components\TextInput::make('sslc_store_password')
                            ->label('Store Password')
                            ->password()
                            ->required(),
                    ]),

                // EPS Credentials
                Forms\Components\Section::make('EPS Credentials')
                    ->visible(fn ($get) => $get('drive_type') === 'merchant' && $get('name') === 'EPS')
                    ->schema([
                        Forms\Components\TextInput::make('eps_merchant_id')
                            ->label('Merchant ID')
                            ->required(),
                        Forms\Components\TextInput::make('eps_store_id')
                            ->label('Store ID')
                            ->required(),
                        Forms\Components\TextInput::make('eps_hash_key')
                            ->label('Hash Key')
                            ->password()
                            ->required(),
                        Forms\Components\TextInput::make('eps_user_name')
                            ->label('API Username')
                            ->required(),
                        Forms\Components\TextInput::make('eps_password')
                            ->label('API Password')
                            ->password()
                            ->required(),
                    ]),

                // Personal: Number and Instructions
                Forms\Components\TextInput::make('personal_number')
                    ->label('Personal Number')
                    ->placeholder('01XXXXXXXXX')
                    ->visible(fn ($get) => $get('drive_type') === 'personal')
                    ->required(fn ($get) => $get('drive_type') === 'personal'),

                Forms\Components\Textarea::make('instruction')
                    ->label('Payment Instructions')
                    ->placeholder('e.g., Send money to this number and provide the transaction ID')
                    ->rows(3)
                    ->visible(fn ($get) => $get('drive_type') === 'personal')
                    ->required(fn ($get) => $get('drive_type') === 'personal'),

                Forms\Components\Toggle::make('is_sandbox')
                    ->label('Sandbox Mode')
                    ->helperText('Enable for testing. Disable for live production.')
                    ->default(true)
                    ->visible(fn ($get) => $get('drive_type') === 'merchant'),
            ]),
            Forms\Components\Section::make('Charges & Limits')->schema([
                Forms\Components\TextInput::make('percent_charge')
                    ->numeric()
                    ->suffix('%')
                    ->default(0),

                Forms\Components\TextInput::make('min_amount')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('max_amount')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('fixed_charge')
                    ->numeric()
                    ->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('logo')->label('Logo')->circular()->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->display_name ?? $record->name) . '&background=0D8ABC&color=fff'),
            Tables\Columns\TextColumn::make('display_name')->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Driver'),
            Tables\Columns\TextColumn::make('drive_type')->label('Type')->badge(fn ($state) => match($state) {
                'automation' => 'Automation',
                'merchant' => 'Merchant',
                'personal' => 'Personal',
            }),
            Tables\Columns\TextColumn::make('username')->label('Username'),
            Tables\Columns\TextColumn::make('personal_number')->label('Number'),
            Tables\Columns\ToggleColumn::make('is_sandbox')->label('Sandbox'),
            Tables\Columns\ToggleColumn::make('status'),
        ])
        ->actions([
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
            'index' => Pages\ListGateways::route('/'),
            'create' => Pages\CreateGateway::route('/create'),
            'edit' => Pages\EditGateway::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_gateways');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_gateways');
    }
}
