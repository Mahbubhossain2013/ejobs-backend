<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon      = 'heroicon-o-document-text';
    protected static ?string $navigationLabel     = 'Invoices';
    protected static ?string $navigationGroup     = 'Finance';
    protected static ?int    $navigationSort      = 1;
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getNavigationBadge(): ?string
    {
        return (string) Invoice::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ==============================================================
    // FORM
    // ==============================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Invoice Details')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->disabled()
                        ->placeholder('Auto-generated'),

                    Forms\Components\Select::make('type')
                        ->label('Invoice Type')
                        ->options([
                            'subscription'      => 'Subscription',
                            'job_boost'         => 'Job Boost',
                            'featured_profile'  => 'Featured Profile',
                            'wallet_deposit'    => 'Wallet Deposit',
                            'wallet_withdrawal' => 'Wallet Withdrawal',
                            'milestone'         => 'Milestone Payment',
                            'contract_payment'  => 'Contract Payment',
                            'service_fee'       => 'Service Fee',
                            'escrow_funding'    => 'Escrow Funding',
                            'escrow_release'    => 'Escrow Release',
                            'refund'            => 'Refund',
                            'manual'            => 'Manual',
                        ])
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'draft'          => 'Draft',
                            'pending'        => 'Pending',
                            'paid'           => 'Paid',
                            'overdue'        => 'Overdue',
                            'partially_paid' => 'Partially Paid',
                            'refunded'       => 'Refunded',
                            'cancelled'      => 'Cancelled',
                            'void'           => 'Void',
                        ])
                        ->required(),
                ]),

            Forms\Components\Section::make('User & Assignment')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('currency_code')
                        ->label('Currency')
                        ->default('BDT')
                        ->maxLength(10),

                    Forms\Components\DatePicker::make('due_date')
                        ->label('Due Date'),
                ]),

            Forms\Components\Section::make('Billing Information')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('billing_name')->label('Name'),
                    Forms\Components\TextInput::make('billing_email')->label('Email')->email(),
                    Forms\Components\TextInput::make('billing_company')->label('Company'),
                    Forms\Components\TextInput::make('billing_vat_number')->label('VAT Number'),
                    Forms\Components\TextInput::make('billing_country')->label('Country Code'),
                    Forms\Components\Textarea::make('billing_address')->label('Address')->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Amounts')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('subtotal')
                        ->label('Subtotal')
                        ->numeric()
                        ->prefix('৳')
                        ->disabled(),

                    Forms\Components\TextInput::make('tax_rate')
                        ->label('Tax Rate (%)')
                        ->numeric()
                        ->suffix('%'),

                    Forms\Components\TextInput::make('tax_amount')
                        ->label('Tax Amount')
                        ->numeric()
                        ->prefix('৳')
                        ->disabled(),

                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Discount')
                        ->numeric()
                        ->prefix('৳'),

                    Forms\Components\TextInput::make('platform_fee')
                        ->label('Platform Fee')
                        ->numeric()
                        ->prefix('৳')
                        ->disabled(),

                    Forms\Components\TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->numeric()
                        ->prefix('৳')
                        ->disabled(),
                ]),

            Forms\Components\Section::make('Notes & Terms')
                ->collapsed()
                ->schema([
                    Forms\Components\Textarea::make('notes')->label('Notes')->rows(3),
                    Forms\Components\Textarea::make('terms')->label('Terms & Conditions')->rows(3),
                    Forms\Components\Textarea::make('payment_instructions')->label('Payment Instructions')->rows(3),
                    Forms\Components\Textarea::make('footer')->label('Footer Text')->rows(2),
                ]),
        ]);
    }

    // ==============================================================
    // TABLE
    // ==============================================================

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'subscription'     => 'primary',
                        'milestone'        => 'success',
                        'job_boost'        => 'warning',
                        'wallet_deposit'   => 'info',
                        'wallet_withdrawal'=> 'info',
                        'refund'           => 'gray',
                        'void'             => 'gray',
                        'manual'           => 'gray',
                        'escrow_funding'   => 'danger',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match($state) {
                        'subscription'      => 'Subscription',
                        'job_boost'         => 'Job Boost',
                        'featured_profile'  => 'Featured',
                        'wallet_deposit'    => 'Deposit',
                        'wallet_withdrawal' => 'Withdrawal',
                        'milestone'         => 'Milestone',
                        'contract_payment'  => 'Contract',
                        'service_fee'       => 'Service Fee',
                        'escrow_funding'    => 'Escrow Fund',
                        'escrow_release'    => 'Escrow Release',
                        'refund'            => 'Refund',
                        'manual'            => 'Manual',
                        default             => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('BDT')
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'            => 'success',
                        'pending'         => 'warning',
                        'overdue'         => 'danger',
                        'void'            => 'danger',
                        'refunded'        => 'gray',
                        'cancelled'       => 'gray',
                        'draft'           => 'gray',
                        'partially_paid'  => 'info',
                        default           => 'gray',
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn($record) => $record->isOverdue() ? 'danger' : null),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('pdf_path')
                    ->label('PDF')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-arrow-down')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'subscription'      => 'Subscription',
                        'job_boost'         => 'Job Boost',
                        'featured_profile'  => 'Featured Profile',
                        'wallet_deposit'    => 'Wallet Deposit',
                        'wallet_withdrawal' => 'Wallet Withdrawal',
                        'milestone'         => 'Milestone',
                        'contract_payment'  => 'Contract Payment',
                        'service_fee'       => 'Service Fee',
                        'escrow_funding'    => 'Escrow Funding',
                        'escrow_release'    => 'Escrow Release',
                        'refund'            => 'Refund',
                        'manual'            => 'Manual',
                    ])
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'draft'          => 'Draft',
                        'pending'        => 'Pending',
                        'paid'           => 'Paid',
                        'overdue'        => 'Overdue',
                        'partially_paid' => 'Partially Paid',
                        'refunded'       => 'Refunded',
                        'cancelled'      => 'Cancelled',
                        'void'           => 'Void',
                    ])
                    ->multiple(),

                Filter::make('overdue')
                    ->label('Overdue Only')
                    ->query(fn(Builder $query) => $query->overdue())
                    ->toggle(),

                Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn(Builder $query) => $query->thisMonth())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_invoice')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),

                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Invoice $record) => in_array($record->status, ['pending', 'overdue']))
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        app(InvoiceService::class)->markAsPaid($record, ['method' => 'manual_admin']);
                        Notification::make()->title('Invoice marked as paid.')->success()->send();
                    }),

                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Invoice $record) {
                        if (!$record->pdf_path) {
                            app(InvoiceService::class)->regeneratePdf($record);
                            $record->refresh();
                        }
                        return response()->download(Storage::disk('public')->path($record->pdf_path));
                    }),

                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->action(function (Invoice $record) {
                        app(InvoiceService::class)->sendByEmail($record);
                        Notification::make()->title('Invoice email queued.')->success()->send();
                    }),

                Tables\Actions\Action::make('regenerate_pdf')
                    ->label('Regen PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        app(InvoiceService::class)->regeneratePdf($record);
                        Notification::make()->title('PDF regenerated.')->success()->send();
                    }),

                Tables\Actions\Action::make('void_invoice')
                    ->label('Void')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn(Invoice $record) => !in_array($record->status, ['paid', 'void', 'refunded']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Void Reason')->required(),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        app(InvoiceService::class)->voidInvoice($record, $data['reason']);
                        Notification::make()->title('Invoice voided.')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_send_email')
                    ->label('Send Emails')
                    ->icon('heroicon-o-envelope')
                    ->action(function ($records) {
                        $service = app(InvoiceService::class);
                        foreach ($records as $record) {
                            try { $service->sendByEmail($record); } catch (\Throwable) {}
                        }
                        Notification::make()->title('Emails queued for selected invoices.')->success()->send();
                    }),

                Tables\Actions\BulkAction::make('bulk_regenerate_pdf')
                    ->label('Regenerate PDFs')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $service = app(InvoiceService::class);
                        foreach ($records as $record) {
                            try { $service->regeneratePdf($record); } catch (\Throwable) {}
                        }
                        Notification::make()->title('PDFs regenerated.')->success()->send();
                    }),

                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    // ==============================================================
    // PAGES
    // ==============================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateManualInvoice::route('/create'),
            'view'   => Pages\ViewInvoice::route('/{record}'),
            'edit'   => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('view_invoices');
    }

    public static function canViewNavigation(): bool
    {
        return auth()->user()->hasPermissionTo('view_invoices');
    }
}
