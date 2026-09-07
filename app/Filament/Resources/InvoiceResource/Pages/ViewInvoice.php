<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PdfGeneratorService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components as Info;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => in_array($this->record->status, ['pending', 'overdue', 'partially_paid']))
                ->requiresConfirmation()
                ->action(function () {
                    app(InvoiceService::class)->markAsPaid($this->record, ['method' => 'manual_admin']);
                    $this->record->refresh();
                    Notification::make()->title('Invoice marked as paid.')->success()->send();
                }),

            Action::make('mark_refunded')
                ->label('Issue Refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn() => $this->record->status === 'paid')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Refund Amount')
                        ->numeric()
                        ->default(fn() => $this->record->total_amount)
                        ->required(),
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $refund = app(InvoiceService::class)->refundInvoice($this->record, $data['amount'], $data['reason']);
                    $this->record->refresh();
                    Notification::make()->title("Refund invoice #{$refund->invoice_number} created.")->success()->send();
                }),

            Action::make('void')
                ->label('Void Invoice')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn() => !in_array($this->record->status, ['paid', 'void', 'refunded']))
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')->label('Void Reason')->required(),
                ])
                ->action(function (array $data) {
                    app(InvoiceService::class)->voidInvoice($this->record, $data['reason']);
                    $this->record->refresh();
                    Notification::make()->title('Invoice voided.')->success()->send();
                }),

            Action::make('send_email')
                ->label('Send Email')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->action(function () {
                    app(InvoiceService::class)->sendByEmail($this->record);
                    Notification::make()->title('Invoice email queued.')->success()->send();
                }),

            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    if (!$this->record->pdf_path) {
                        app(InvoiceService::class)->regeneratePdf($this->record);
                        $this->record->refresh();
                    }
                    return response()->download(Storage::disk('public')->path($this->record->pdf_path));
                }),

            Action::make('regenerate_pdf')
                ->label('Regenerate PDF')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    app(InvoiceService::class)->regeneratePdf($this->record);
                    $this->record->refresh();
                    Notification::make()->title('PDF regenerated.')->success()->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Info\Section::make('Invoice Summary')
                ->columns(4)
                ->schema([
                    Info\TextEntry::make('invoice_number')
                        ->label('Invoice Number')
                        ->weight('bold')
                        ->size('lg'),

                    Info\TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'paid' => 'success',
                            'pending' => 'warning',
                            'overdue', 'void' => 'danger',
                            'refunded', 'cancelled', 'draft' => 'gray',
                            'partially_paid' => 'info',
                            default => 'gray',
                        }),

                    Info\TextEntry::make('type')
                        ->badge()
                        ->formatStateUsing(fn($state) => ucwords(str_replace('_', ' ', $state))),

                    Info\TextEntry::make('total_amount')
                        ->label('Total Amount')
                        ->money('BDT')
                        ->weight('bold')
                        ->size('lg'),

                    Info\TextEntry::make('issued_at')
                        ->label('Issued')
                        ->dateTime(),

                    Info\TextEntry::make('due_date')
                        ->label('Due Date')
                        ->date(),

                    Info\TextEntry::make('paid_at')
                        ->label('Paid At')
                        ->dateTime(),

                    Info\TextEntry::make('currency_code')
                        ->label('Currency'),
                ]),

            Info\Section::make('Billing Information')
                ->columns(3)
                ->schema([
                    Info\TextEntry::make('billing_name')->label('Name'),
                    Info\TextEntry::make('billing_email')->label('Email'),
                    Info\TextEntry::make('billing_company')->label('Company'),
                    Info\TextEntry::make('billing_country')->label('Country'),
                    Info\TextEntry::make('billing_vat_number')->label('VAT Number'),
                    Info\TextEntry::make('billing_address')->label('Address'),
                ]),

            Info\Section::make('Amount Breakdown')
                ->columns(4)
                ->schema([
                    Info\TextEntry::make('subtotal')->label('Subtotal')->money('BDT'),
                    Info\TextEntry::make('tax_amount')->label('Tax')->money('BDT'),
                    Info\TextEntry::make('discount_amount')->label('Discount')->money('BDT'),
                    Info\TextEntry::make('platform_fee')->label('Platform Fee')->money('BDT'),
                    Info\TextEntry::make('total_amount')->label('Total')->money('BDT')->weight('bold'),
                    Info\TextEntry::make('amount_paid')->label('Paid')->money('BDT'),
                    Info\TextEntry::make('amount_due')->label('Amount Due')->money('BDT')->color('danger'),
                ]),

            Info\Section::make('Line Items')
                ->schema([
                    Info\RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Info\TextEntry::make('description')->label('Description'),
                            Info\TextEntry::make('quantity')->label('Qty'),
                            Info\TextEntry::make('unit_price')->label('Unit Price')->money('BDT'),
                            Info\TextEntry::make('subtotal')->label('Subtotal')->money('BDT'),
                        ])
                        ->columns(4),
                ]),

            Info\Section::make('Payment History')
                ->schema([
                    Info\RepeatableEntry::make('transactions')
                        ->label('')
                        ->schema([
                            Info\TextEntry::make('type')->badge(),
                            Info\TextEntry::make('status')->badge()
                                ->colors(['success' => 'completed', 'warning' => 'pending', 'danger' => 'failed']),
                            Info\TextEntry::make('amount')->money('BDT'),
                            Info\TextEntry::make('payment_method')->label('Method'),
                            Info\TextEntry::make('processed_at')->label('Processed')->dateTime(),
                        ])
                        ->columns(5),
                ]),

            Info\Section::make('Activity Log')
                ->schema([
                    Info\RepeatableEntry::make('logs')
                        ->label('')
                        ->schema([
                            Info\TextEntry::make('event')->badge()->color('gray'),
                            Info\TextEntry::make('description'),
                            Info\TextEntry::make('actor_type')->label('By'),
                            Info\TextEntry::make('occurred_at')->label('When')->dateTime(),
                        ])
                        ->columns(4),
                ]),

            Info\Section::make('Notes & Terms')
                ->collapsed()
                ->schema([
                    Info\TextEntry::make('notes')->label('Notes')->html(),
                    Info\TextEntry::make('terms')->label('Terms & Conditions')->html(),
                    Info\TextEntry::make('payment_instructions')->label('Payment Instructions')->html(),
                ]),
        ]);
    }
}
