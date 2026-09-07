<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingProfileResource\Pages;
use App\Models\BillingProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BillingProfileResource extends Resource
{
    protected static ?string $model           = BillingProfile::class;
    protected static ?string $navigationIcon  = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Billing Profiles';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profile Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('billing_name')->label('Billing Name'),
                    Forms\Components\TextInput::make('company_name')->label('Company Name'),
                    Forms\Components\TextInput::make('email')->label('Billing Email')->email(),
                    Forms\Components\TextInput::make('phone')->label('Phone'),
                    Forms\Components\TextInput::make('vat_number')->label('VAT Number'),
                    Forms\Components\TextInput::make('tax_id')->label('Tax ID'),
                    Forms\Components\TextInput::make('currency_code')->label('Currency')->default('USD'),
                    Forms\Components\TextInput::make('country_code')->label('Country Code (ISO)')->maxLength(3),
                    Forms\Components\TextInput::make('state')->label('State / Province'),
                    Forms\Components\TextInput::make('city')->label('City'),
                    Forms\Components\TextInput::make('postal_code')->label('Postal Code'),
                    Forms\Components\Textarea::make('address_line_1')->label('Address Line 1')->columnSpanFull(),
                    Forms\Components\Textarea::make('address_line_2')->label('Address Line 2')->columnSpanFull(),
                    Forms\Components\Toggle::make('is_business')->label('Business Account'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('billing_name')->label('Billing Name')->searchable(),
                Tables\Columns\TextColumn::make('company_name')->label('Company')->searchable()->placeholder('N/A'),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country_code')->label('Country'),
                Tables\Columns\TextColumn::make('vat_number')->label('VAT #')->placeholder('N/A'),
                Tables\Columns\IconColumn::make('is_business')->label('Business')->boolean(),
                Tables\Columns\TextColumn::make('currency_code')->label('Currency'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBillingProfiles::route('/'),
            'create' => Pages\CreateBillingProfile::route('/create'),
            'edit'   => Pages\EditBillingProfile::route('/{record}/edit'),
        ];
    }
}
