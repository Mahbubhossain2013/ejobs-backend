<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class CreateManualInvoice extends Page
{
    protected static string $resource = InvoiceResource::class;
    protected static string $view     = 'filament.pages.create-manual-invoice';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'type'         => 'manual',
            'status'       => 'pending',
            'currency_code'=> 'BDT',
            'due_date'     => now()->addDays(30)->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Bill To (User)')
                            ->options(User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'manual'           => 'Manual Invoice',
                                'service_fee'      => 'Service Fee',
                                'subscription'     => 'Subscription',
                                'job_boost'        => 'Job Boost',
                                'milestone'        => 'Milestone',
                                'escrow_funding'   => 'Escrow Funding',
                                'escrow_release'   => 'Escrow Release',
                                'wallet_deposit'   => 'Wallet Deposit',
                                'wallet_withdrawal'=> 'Wallet Withdrawal',
                                'refund'           => 'Refund',
                            ])
                            ->default('manual')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft'   => 'Draft',
                                'pending' => 'Pending',
                                'paid'    => 'Paid',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\TextInput::make('currency_code')
                            ->label('Currency')
                            ->default('BDT')
                            ->maxLength(10),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(now()->addDays(30)),

                        Forms\Components\TextInput::make('tax_rate')
                            ->label('Tax Rate (%)')
                            ->numeric()
                            ->default(0)
                            ->suffix('%'),
                    ]),

                Forms\Components\Section::make('Billing Info')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('billing_name')->label('Name'),
                        Forms\Components\TextInput::make('billing_email')->label('Email')->email(),
                        Forms\Components\TextInput::make('billing_company')->label('Company'),
                        Forms\Components\TextInput::make('billing_vat_number')->label('VAT Number'),
                        Forms\Components\TextInput::make('billing_country')->label('Country'),
                        Forms\Components\Textarea::make('billing_address')->label('Address')->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('description')->label('Description')->required()->columnSpan(3),
                                Forms\Components\TextInput::make('quantity')->label('Qty')->numeric()->default(1)->columnSpan(1),
                                Forms\Components\TextInput::make('unit_price')->label('Unit Price (৳)')->numeric()->required()->columnSpan(2),
                                Forms\Components\TextInput::make('tax_rate')->label('Tax %')->numeric()->default(0)->columnSpan(1),
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options(['service' => 'Service', 'fee' => 'Fee', 'credit' => 'Credit', 'refund' => 'Refund'])
                                    ->default('service')
                                    ->columnSpan(1),
                            ])
                            ->columns(8)
                            ->addActionLabel('+ Add Line Item')
                            ->minItems(1)
                            ->defaultItems(1),
                    ]),

                Forms\Components\Section::make('Notes & Terms')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')->label('Notes')->rows(3),
                        Forms\Components\Textarea::make('terms')->label('Terms & Conditions')->rows(3),
                        Forms\Components\Textarea::make('payment_instructions')->label('Payment Instructions')->rows(3),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Create Invoice')
                ->submit('create'),

            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(InvoiceResource::getUrl()),
        ];
    }

    public function create(): void
    {
        $data  = $this->form->getState();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $service = app(InvoiceService::class);
        $invoice = $service->createInvoice($data, $items);

        Notification::make()
            ->title("Invoice #{$invoice->invoice_number} created successfully.")
            ->success()
            ->send();

        $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
    }
}
