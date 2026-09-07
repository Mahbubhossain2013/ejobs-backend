<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceTemplateResource\Pages;
use App\Models\InvoiceTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class InvoiceTemplateResource extends Resource
{
    protected static ?string $model            = InvoiceTemplate::class;
    protected static ?string $navigationIcon   = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel  = 'Invoice Templates';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int    $navigationSort   = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Template Settings')
                ->columnSpanFull()
                ->tabs([
                    // ── Tab 1: General ─────────────────────────────
                    Forms\Components\Tabs\Tab::make('General')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(100),

                            Forms\Components\TextInput::make('slug')
                                ->label('Slug (URL key)')
                                ->maxLength(100)
                                ->unique(ignoreRecord: true),

                            Forms\Components\Select::make('template_view')
                                ->label('Base Template')
                                ->options([
                                    'invoices.templates.default'      => 'Default (Professional)',
                                    'invoices.templates.minimal'      => 'Minimal',
                                    'invoices.templates.professional' => 'Corporate',
                                ])
                                ->default('invoices.templates.default')
                                ->required(),

                            Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                            Forms\Components\Toggle::make('is_default')->label('Set as Default'),
                        ])->columns(2),

                    // ── Tab 2: Branding ────────────────────────────
                    Forms\Components\Tabs\Tab::make('Branding')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\FileUpload::make('logo_path')
                                ->label('Company Logo')
                                ->image()
                                ->directory('invoices/logos')
                                ->disk('public')
                                ->imagePreviewHeight('80')
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('watermark_text')
                                ->label('Watermark Text')
                                ->placeholder('e.g. PAID, CONFIDENTIAL'),

                            Forms\Components\FileUpload::make('watermark_path')
                                ->label('Watermark Image')
                                ->image()
                                ->directory('invoices/watermarks')
                                ->disk('public'),

                            Forms\Components\ColorPicker::make('primary_color')
                                ->label('Primary Color')
                                ->default('#1a56db'),

                            Forms\Components\ColorPicker::make('secondary_color')
                                ->label('Secondary Color')
                                ->default('#e1effe'),

                            Forms\Components\ColorPicker::make('accent_color')
                                ->label('Accent Color')
                                ->default('#1c64f2'),

                            Forms\Components\ColorPicker::make('text_color')
                                ->label('Text Color')
                                ->default('#111827'),
                        ])->columns(2),

                    // ── Tab 3: Company Info ────────────────────────
                    Forms\Components\Tabs\Tab::make('Company Info')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\TextInput::make('company_name')->label('Company Name'),
                            Forms\Components\TextInput::make('company_email')->label('Email')->email(),
                            Forms\Components\TextInput::make('company_phone')->label('Phone'),
                            Forms\Components\TextInput::make('company_website')->label('Website')->url(),
                            Forms\Components\TextInput::make('company_vat_label')->label('VAT Label')->default('VAT No.'),
                            Forms\Components\TextInput::make('company_vat_number')->label('VAT Number'),
                            Forms\Components\Textarea::make('company_address')->label('Address')->rows(3)->columnSpanFull(),
                        ])->columns(2),

                    // ── Tab 4: Invoice Content ─────────────────────
                    Forms\Components\Tabs\Tab::make('Invoice Content')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\TextInput::make('tax_label')->label('Tax Label')->default('VAT'),
                            Forms\Components\TextInput::make('currency_symbol')->label('Currency Symbol')->default('$'),
                            Forms\Components\Textarea::make('footer_text')->label('Footer Text')->rows(3),
                            Forms\Components\Textarea::make('payment_instructions')->label('Payment Instructions')->rows(3),
                            Forms\Components\Textarea::make('terms_and_conditions')->label('Terms & Conditions')->rows(5)->columnSpanFull(),
                        ])->columns(2),

                    // ── Tab 5: Advanced CSS ────────────────────────
                    Forms\Components\Tabs\Tab::make('Custom CSS')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Forms\Components\Textarea::make('custom_css')
                                ->label('Custom CSS')
                                ->rows(15)
                                ->columnSpanFull()
                                ->placeholder("/* Override invoice styles */\n.invoice-header { background: #1a56db; }"),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('template_view')
                    ->label('Base View')
                    ->formatStateUsing(fn($state) => basename(str_replace('.', '/', $state))),

                Tables\Columns\ColorColumn::make('primary_color')->label('Color'),

                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\IconColumn::make('is_default')->label('Default')->boolean(),

                Tables\Columns\TextColumn::make('invoices_count')
                    ->label('Invoices')
                    ->counts('invoices')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn($record) => !$record->is_default)
                    ->action(function ($record) {
                        $record->setAsDefault();
                        Notification::make()->title('Template set as default.')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoiceTemplates::route('/'),
            'create' => Pages\CreateInvoiceTemplate::route('/create'),
            'edit'   => Pages\EditInvoiceTemplate::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_invoice_templates');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('manage_invoice_templates');
    }
}
