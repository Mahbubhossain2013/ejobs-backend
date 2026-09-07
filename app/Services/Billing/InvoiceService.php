<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\InvoiceTransaction;
use App\Models\PaymentLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Events\Billing\InvoiceCreated;
use App\Events\Billing\InvoicePaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        protected TaxCalculationService $taxService,
        protected PdfGeneratorService   $pdfService,
    ) {}

    // =========================================================
    // CREATE
    // =========================================================

    /**
     * Create a new invoice with line items.
     *
     * @param array $data     Top-level invoice fields
     * @param array $items    Each item: [description, quantity, unit_price, tax_rate?, type?]
     */
    public function createInvoice(array $data, array $items = []): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            // 1. Resolve template
            $template = isset($data['invoice_template_id'])
                ? InvoiceTemplate::find($data['invoice_template_id'])
                : InvoiceTemplate::getDefault();

            $data['invoice_template_id'] = $template?->id;

            // 2. Generate invoice number if not set
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = Invoice::generateInvoiceNumber($data['type'] ?? 'manual');
            }

            // 3. Set issued_at
            $data['issued_at'] ??= now();

            // 4. Set due_date (default 30 days)
            $data['due_date'] ??= now()->addDays(30)->toDateString();

            // 5. Snapshot billing profile from user
            if (!empty($data['user_id']) && empty($data['billing_name'])) {
                $user = User::with('billingProfile')->find($data['user_id']);
                if ($user && $profile = $user->billingProfile) {
                    $data['billing_name']       ??= $profile->display_name;
                    $data['billing_email']      ??= $profile->email ?? $user->email;
                    $data['billing_company']    ??= $profile->company_name;
                    $data['billing_address']    ??= $profile->full_address;
                    $data['billing_vat_number'] ??= $profile->vat_number;
                    $data['billing_country']    ??= $profile->country_code;
                } elseif ($user) {
                    $data['billing_name']  ??= $user->name;
                    $data['billing_email'] ??= $user->email;
                }
            }

            // 6. Create invoice
            $invoice = Invoice::create(array_merge([
                'status'       => 'pending',
                'currency_code'=> 'BDT',
                'subtotal'     => 0,
                'tax_amount'   => 0,
                'total_amount' => 0,
                'amount_paid'  => 0,
                'amount_due'   => 0,
            ], $data));

            // 7. Create line items
            if (!empty($items)) {
                $sortOrder = 0;
                foreach ($items as $itemData) {
                    $item = new InvoiceItem(array_merge(['sort_order' => $sortOrder++], $itemData));
                    $item->invoice_id = $invoice->id;
                    $item->computeTotals();
                    $item->save();
                }
            }

            // 8. Recalculate invoice totals from items
            $invoice = $this->recalculateTotals($invoice);

            // 9. Generate PDF asynchronously (dispatch job)
            try {
                $this->pdfService->generate($invoice);
            } catch (\Throwable $e) {
                Log::warning('Invoice PDF generation failed: ' . $e->getMessage(), ['invoice_id' => $invoice->id]);
            }

            // 10. Record audit log
            PaymentLog::record($invoice, 'invoice.created', "Invoice #{$invoice->invoice_number} created.");

            // 11. Fire event
            event(new InvoiceCreated($invoice));

            return $invoice->fresh(['items', 'template']);
        });
    }

    // =========================================================
    // RECALCULATE TOTALS
    // =========================================================

    public function recalculateTotals(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('items');

        $subtotal = $invoice->items->sum(fn($i) => (float) $i->subtotal);

        // Tax calculation (use existing tax_rate if set, otherwise auto-detect)
        $taxRate   = (float) ($invoice->tax_rate ?? 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);

        // Platform fee
        $platformFee = round($subtotal * ((float) ($invoice->platform_fee_rate ?? 0) / 100), 2);

        $discount = (float) ($invoice->discount_amount ?? 0);
        $total    = $subtotal - $discount + $taxAmount + $platformFee;
        $amountDue = max(0, $total - (float) ($invoice->amount_paid ?? 0));

        $invoice->update([
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmount,
            'platform_fee' => $platformFee,
            'total_amount' => $total,
            'amount_due'   => $amountDue,
        ]);

        return $invoice->fresh();
    }

    // =========================================================
    // MARK AS PAID
    // =========================================================

    /**
     * Mark an invoice as paid and record the transaction.
     *
     * @param array $paymentData  [method, gateway, transaction_id, amount]
     */
    public function markAsPaid(Invoice $invoice, array $paymentData = []): void
    {
        DB::transaction(function () use ($invoice, $paymentData) {
            // Idempotency guard: skip if already paid
            $invoice->refresh();
            if ($invoice->status === 'paid') {
                return;
            }

            $amount = $paymentData['amount'] ?? $invoice->total_amount;

            // Create transaction record
            InvoiceTransaction::create([
                'invoice_id'             => $invoice->id,
                'user_id'                => $invoice->user_id,
                'type'                   => 'payment',
                'status'                 => 'completed',
                'amount'                 => $amount,
                'currency_code'          => $invoice->currency_code,
                'payment_method'         => $paymentData['method'] ?? 'wallet',
                'payment_gateway'        => $paymentData['gateway'] ?? null,
                'gateway_transaction_id' => $paymentData['transaction_id'] ?? null,
                'processed_at'           => now(),
            ]);

            $invoice->update([
                'status'                 => 'paid',
                'amount_paid'            => $amount,
                'amount_due'             => 0,
                'paid_at'                => now(),
                'payment_method'         => $paymentData['method'] ?? null,
                'payment_gateway'        => $paymentData['gateway'] ?? null,
                'payment_transaction_id' => $paymentData['transaction_id'] ?? null,
            ]);

            PaymentLog::record($invoice, 'invoice.paid', "Invoice #{$invoice->invoice_number} marked as paid. Amount: {$amount} {$invoice->currency_code}.", [], $amount);

            event(new InvoicePaid($invoice));
        });
    }

    // =========================================================
    // APPLY WALLET PAYMENT
    // =========================================================

    /**
     * Deduct from user's wallet and mark invoice as paid.
     */
    public function applyWalletPayment(Invoice $invoice): void
    {
        $user   = $invoice->user;
        $wallet = $user->wallet;

        if (!$wallet) {
            throw new \RuntimeException("User #{$user->id} has no wallet.");
        }

        DB::transaction(function () use ($invoice, $wallet) {
            $balanceBefore = (float) $wallet->balance;

            // Debit wallet
            $wallet->debit(
                $invoice->amount_due,
                Invoice::class,
                $invoice->id,
                "Payment for Invoice #{$invoice->invoice_number}"
            );

            $balanceAfter = (float) $wallet->fresh()->balance;

            // Link wallet transaction to invoice
            WalletTransaction::where('reference_type', Invoice::class)
                ->where('reference_id', $invoice->id)
                ->latest()
                ->first()
                ?->update([
                    'invoice_id'    => $invoice->id,
                    'balance_after' => $balanceAfter,
                    'currency_code' => $invoice->currency_code,
                ]);

            // Mark invoice paid
            $this->markAsPaid($invoice, [
                'method' => 'wallet',
                'amount' => $invoice->amount_due,
            ]);

            PaymentLog::record($invoice, 'wallet.payment', "Wallet debited {$invoice->amount_due} {$invoice->currency_code}. Balance: {$balanceBefore} → {$balanceAfter}.", [], $invoice->amount_due);
        });
    }

    // =========================================================
    // REFUND
    // =========================================================

    /**
     * Create a refund invoice linked to the original.
     */
    public function refundInvoice(Invoice $invoice, float $amount, string $reason = ''): Invoice
    {
        return DB::transaction(function () use ($invoice, $amount, $reason) {
            $refund = $this->createInvoice([
                'type'              => 'refund',
                'user_id'           => $invoice->user_id,
                'candidate_id'      => $invoice->candidate_id,
                'employer_id'       => $invoice->employer_id,
                'parent_invoice_id' => $invoice->id,
                'status'            => 'paid',
                'currency_code'     => $invoice->currency_code,
                'billing_name'      => $invoice->billing_name,
                'billing_email'     => $invoice->billing_email,
                'billing_company'   => $invoice->billing_company,
                'billing_address'   => $invoice->billing_address,
                'billing_country'   => $invoice->billing_country,
                'notes'             => "Refund for Invoice #{$invoice->invoice_number}. Reason: {$reason}",
                'paid_at'           => now(),
                'amount_paid'       => $amount,
                'amount_due'        => 0,
            ], [
                [
                    'description' => "Refund – " . $invoice->typeLabel(),
                    'details'     => "Refund for Invoice #{$invoice->invoice_number}",
                    'quantity'    => 1,
                    'unit_price'  => $amount,
                    'type'        => 'refund',
                ],
            ]);

            // Credit wallet
            $wallet = $invoice->user->wallet;
            if ($wallet) {
                $wallet->credit(
                    $amount,
                    Invoice::class,
                    $refund->id,
                    "Refund for Invoice #{$invoice->invoice_number}"
                );
            }

            // Update original invoice
            $invoice->update(['status' => 'refunded']);

            PaymentLog::record($invoice, 'invoice.refunded', "Refund of {$amount} issued. Refund Invoice #{$refund->invoice_number}.", ['reason' => $reason], $amount);

            return $refund;
        });
    }

    // =========================================================
    // VOID
    // =========================================================

    public function voidInvoice(Invoice $invoice, string $reason = ''): void
    {
        $invoice->update(['status' => 'void']);
        PaymentLog::record($invoice, 'invoice.voided', "Invoice #{$invoice->invoice_number} voided. Reason: {$reason}.");
    }

    // =========================================================
    // REGENERATE PDF
    // =========================================================

    public function regeneratePdf(Invoice $invoice): string
    {
        $path = $this->pdfService->generate($invoice);
        PaymentLog::record($invoice, 'pdf.generated', "PDF regenerated for Invoice #{$invoice->invoice_number}.");
        return $path;
    }

    // =========================================================
    // SEND EMAIL
    // =========================================================

    public function sendByEmail(Invoice $invoice, ?string $toEmail = null): void
    {
        $toEmail = $toEmail ?? $invoice->billing_email ?? $invoice->user?->email;

        if (!$toEmail) {
            throw new \RuntimeException("No email address found for invoice #{$invoice->invoice_number}");
        }

        // Ensure PDF exists
        if (!$invoice->pdf_path) {
            $this->regeneratePdf($invoice);
        }

        // Dispatch email job
        \App\Jobs\Billing\SendInvoiceEmailJob::dispatch($invoice, $toEmail);

        $invoice->update(['sent_at' => now()]);
        PaymentLog::record($invoice, 'invoice.sent', "Invoice #{$invoice->invoice_number} sent to {$toEmail}.");
    }

    // =========================================================
    // MARK OVERDUE
    // =========================================================

    /**
     * Batch-update pending invoices past their due date to "overdue".
     * Called by scheduler.
     */
    public function markOverdueInvoices(): int
    {
        $updated = Invoice::where('status', 'pending')
            ->whereDate('due_date', '<', now())
            ->update(['status' => 'overdue']);

        Log::info("Billing: Marked {$updated} invoices as overdue.");
        return $updated;
    }
}
