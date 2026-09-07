<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Wallet;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Deposit extends Model
{
    protected $fillable = [
        'user_id', 'gateway_id', 'amount', 'charge', 
        'payable', 'transaction_id', 'proof_document', 
        'status', 'admin_feedback'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function invoice()
    {
        return $this->morphOne(Invoice::class, 'reference');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($deposit) {
            if ($deposit->isDirty('status')) {
                $oldStatus = $deposit->getOriginal('status');
                $newStatus = $deposit->status;

                if ($oldStatus === 'pending') {
                    if ($newStatus === 'approved') {
                        DB::transaction(function () use ($deposit) {
                            // 1. Update Wallet Balance
                            $wallet = Wallet::firstOrCreate(
                                ['user_id' => $deposit->user_id],
                                ['balance' => 0, 'locked_balance' => 0, 'withdrawable_balance' => 0]
                            );
                            $wallet->increment('balance', $deposit->amount);
                            $wallet->increment('withdrawable_balance', $deposit->amount);

                            // 2. Create Ledger Transaction Record
                            try {
                                $wallet->transactions()->create([
                                    'wallet_id' => $wallet->id,
                                    'type' => 'credit',
                                    'amount' => $deposit->amount,
                                    'reference_type' => 'deposit',
                                    'reference_id' => $deposit->id,
                                    'description' => "Fund added via " . ($deposit->gateway?->display_name ?? 'Payment Gateway'),
                                    'status' => 'completed'
                                ]);
                            } catch (\Throwable $txEx) {
                                Log::warning("Ledger transaction record failed: " . $txEx->getMessage());
                            }

                            // 3. Mark companion invoice as Paid
                            try {
                                $invoice = $deposit->invoice;
                                if ($invoice) {
                                    $invoiceService = app(InvoiceService::class);
                                    $invoiceService->markAsPaid($invoice, [
                                        'method' => 'gateway_manual',
                                        'gateway' => $deposit->gateway?->name,
                                        'transaction_id' => $deposit->transaction_id,
                                        'amount' => $deposit->payable,
                                    ]);
                                }
                            } catch (\Throwable $invEx) {
                                Log::warning("Invoice mark paid error: " . $invEx->getMessage());
                            }

                            // 4. Notify user
                            try {
                                $user = \App\Models\User::find($deposit->user_id);
                                if ($user) {
                                    app(NotificationService::class)->sendNotification(
                                        $user,
                                        'Deposit Approved',
                                        number_format($deposit->amount, 2) . ' BDT has been added to your wallet successfully.',
                                        'billing',
                                        '/dashboard/wallet'
                                    );
                                }
                            } catch (\Throwable $notifEx) {
                                Log::warning("Deposit notification skipped: " . $notifEx->getMessage());
                            }

                            // 5. Invalidate cached wallet balance
                            Cache::forget("wallet_balance_{$deposit->user_id}");
                        });
                    } elseif ($newStatus === 'rejected') {
                        // Mark companion invoice as void
                        try {
                            $invoice = $deposit->invoice;
                            if ($invoice) {
                                $invoiceService = app(InvoiceService::class);
                                $invoiceService->voidInvoice($invoice, $deposit->admin_feedback ?? 'Deposit request rejected by administrator.');
                            }
                        } catch (\Throwable $voidEx) {
                            Log::warning("Invoice void error: " . $voidEx->getMessage());
                        }
                    }
                }
            }
        });
    }
}