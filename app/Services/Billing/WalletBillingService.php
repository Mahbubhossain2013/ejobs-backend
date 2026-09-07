<?php

namespace App\Services\Billing;

use App\Models\Deposit;
use App\Models\Invoice;
use App\Models\Withdrawal;

class WalletBillingService
{
    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Generate a wallet deposit invoice when a deposit is made.
     */
    public function generateDepositInvoice(Deposit $deposit): Invoice
    {
        $deposit->loadMissing('user');

        return $this->invoiceService->createInvoice([
            'type'           => 'wallet_deposit',
            'user_id'        => $deposit->user_id,
            'reference_type' => Deposit::class,
            'reference_id'   => $deposit->id,
            'currency_code'  => $deposit->currency ?? 'USD',
            'status'         => 'paid',  // deposit = already paid
            'paid_at'        => now(),
            'payment_method' => $deposit->payment_method ?? 'gateway',
            'due_date'       => now()->toDateString(),
            'notes'          => "Wallet deposit via " . ($deposit->gateway ?? 'payment gateway'),
        ], [
            [
                'description' => 'Wallet Top-Up',
                'details'     => 'Credit added to your platform wallet',
                'quantity'    => 1,
                'unit_price'  => $deposit->amount,
                'type'        => 'credit',
            ],
        ]);
    }

    /**
     * Generate a wallet withdrawal invoice when a withdrawal is approved.
     */
    public function generateWithdrawalInvoice(Withdrawal $withdrawal): Invoice
    {
        $withdrawal->loadMissing('user');

        return $this->invoiceService->createInvoice([
            'type'           => 'wallet_withdrawal',
            'user_id'        => $withdrawal->user_id,
            'reference_type' => Withdrawal::class,
            'reference_id'   => $withdrawal->id,
            'currency_code'  => 'USD',
            'status'         => 'paid',
            'paid_at'        => now(),
            'payment_method' => $withdrawal->gateway ?? 'bank_transfer',
            'due_date'       => now()->toDateString(),
            'notes'          => "Withdrawal processed to " . ($withdrawal->gateway ?? 'payout account'),
        ], [
            [
                'description' => 'Wallet Withdrawal',
                'details'     => 'Funds withdrawn from platform wallet',
                'quantity'    => 1,
                'unit_price'  => $withdrawal->amount,
                'type'        => 'service',
            ],
        ]);
    }
}
