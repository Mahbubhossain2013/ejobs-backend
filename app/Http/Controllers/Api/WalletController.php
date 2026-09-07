<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    /**
     * EMPLOYER WALLET
     */
    public function index()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }
            
            // We use direct absolute namespaces (\App\Models\...) to prevent "Use Import" crashes
            $wallet = \App\Models\Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'locked_balance' => 0]
            );

            // Cache wallet balance for instant loading
            $cachedBalance = Cache::remember("wallet_balance_{$userId}", 300, function () use ($wallet) {
                return (float) $wallet->balance;
            });
            $wallet->balance = $cachedBalance;

            $transactions = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)->latest()->take(10)->get();
            $deposits = \App\Models\Deposit::where('user_id', $userId)->with('gateway')->latest()->take(5)->get();
            
            $gateways = \App\Models\Gateway::where('status', 1)->orWhere('status', true)->get();
            if ($gateways->isEmpty()) {
                $gateways = \App\Models\Gateway::all();
            }

            $escrows = \App\Models\Escrow::where('employer_id', $userId)->with('job', 'candidate')->latest()->get();

            return response()->json([
                'status' => true,
                'wallet' => $wallet,
                'transactions' => $transactions,
                'deposits' => $deposits,
                'gateways' => $gateways,
                'escrows' => $escrows,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->validator->errors()->first() ?? 'ভ্যালিডেশন এরর',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Wallet Index Crash: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            
            return response()->json([
                'status' => false, 
                'message' => 'An unexpected error occurred. Please try again.'
            ], 500);
        }
    }

    /**
     * EMPLOYER DEPOSIT
     */
    public function deposit(Request $request)
    {
        try {
            $request->validate([
                'gateway_id' => 'required|exists:gateways,id',
                'amount' => 'required|numeric|min:1',
            ]);

            $gateway = \App\Models\Gateway::findOrFail($request->gateway_id);
            $charge = ($request->amount * $gateway->percent_charge) / 100;
            $payable = $request->amount + $charge;

            if ($request->amount < ($gateway->min_amount ?? 1)) {
                return response()->json([
                    'status' => false,
                    'message' => "Minimum deposit amount for {$gateway->display_name} is ৳" . number_format($gateway->min_amount ?? 1, 2),
                    'error_code' => 'MIN_AMOUNT',
                ], 422);
            }

            if (($gateway->max_amount ?? 0) > 0 && $request->amount > $gateway->max_amount) {
                return response()->json([
                    'status' => false,
                    'message' => "Maximum deposit amount for {$gateway->display_name} is ৳" . number_format($gateway->max_amount, 2),
                    'error_code' => 'MAX_AMOUNT',
                ], 422);
            }

            // AUTOMATION GATEWAY (API Key - e.g., OniPay)
            if ($gateway->drive_type === 'automation') {
                $trxId = 'ONI-' . strtoupper(Str::random(10));
                
                $deposit = \App\Models\Deposit::create([
                    'user_id' => Auth::id(),
                    'gateway_id' => $gateway->id,
                    'amount' => $request->amount,
                    'charge' => $charge,
                    'payable' => $payable,
                    'transaction_id' => $trxId,
                    'status' => 'pending'
                ]);

                // Create companion Invoice
                try {
                    $invoiceService = app(\App\Services\Billing\InvoiceService::class);
                    $user = Auth::user();
                    $candidateId = $user->hasRole('candidate') ? $user->id : null;
                    $employerId = $user->hasRole('employer') ? $user->id : null;

                    $invoiceService->createInvoice([
                        'type' => 'wallet_deposit',
                        'user_id' => $user->id,
                        'candidate_id' => $candidateId,
                        'employer_id' => $employerId,
                        'reference_type' => \App\Models\Deposit::class,
                        'reference_id' => $deposit->id,
                        'currency_code' => 'BDT',
                        'status' => 'pending',
                        'notes' => "Wallet Deposit via {$gateway->display_name}. TrxID: {$trxId}",
                    ], [
                        [
                            'description' => "Wallet Deposit Fund Request",
                            'quantity' => 1,
                            'unit_price' => $request->amount,
                        ],
                        [
                            'description' => "Gateway Fee Charge",
                            'quantity' => 1,
                            'unit_price' => $charge,
                        ]
                    ]);
                } catch (\Throwable $invoiceEx) {
                    Log::error('Deposit Invoice Generation Failed: ' . $invoiceEx->getMessage());
                }

                // Check if OniPayService exists before calling
                if (class_exists(\App\Services\Payment\OniPayService::class)) {
                    $payment = \App\Services\Payment\OniPayService::createOrder($gateway, [
                        'amount' => $payable,
                        'name' => Auth::user()->name,
                        'email' => Auth::user()->email,
                        'phone' => Auth::user()->profile->phone ?? '01000000000',
                        'trx_id' => $trxId
                    ]);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['payment_url'])) {
                        return response()->json(['status' => true, 'redirect_url' => $payment['payment_url']]);
                    }
                    return response()->json(['status' => false, 'message' => $payment['message'] ?? 'Gateway API Error.'], 500);
                }

                return response()->json(['status' => false, 'message' => 'Payment service not available.'], 500);
            }

            // MERCHANT GATEWAY (API Key - e.g., bKash, Nagad, Rocket)
            if ($gateway->drive_type === 'merchant') {
                $trxId = strtoupper($gateway->name) . '-' . strtoupper(Str::random(10));
                
                $deposit = \App\Models\Deposit::create([
                    'user_id' => Auth::id(),
                    'gateway_id' => $gateway->id,
                    'amount' => $request->amount,
                    'charge' => $charge,
                    'payable' => $payable,
                    'transaction_id' => $trxId,
                    'status' => 'pending'
                ]);

                // Create companion Invoice
                try {
                    $invoiceService = app(\App\Services\Billing\InvoiceService::class);
                    $user = Auth::user();
                    $candidateId = $user->hasRole('candidate') ? $user->id : null;
                    $employerId = $user->hasRole('employer') ? $user->id : null;

                    $invoiceService->createInvoice([
                        'type' => 'wallet_deposit',
                        'user_id' => $user->id,
                        'candidate_id' => $candidateId,
                        'employer_id' => $employerId,
                        'reference_type' => \App\Models\Deposit::class,
                        'reference_id' => $deposit->id,
                        'currency_code' => 'BDT',
                        'status' => 'pending',
                        'notes' => "Wallet Deposit via {$gateway->display_name}. TrxID: {$trxId}",
                    ], [
                        [
                            'description' => "Wallet Deposit Fund Request",
                            'quantity' => 1,
                            'unit_price' => $request->amount,
                        ],
                        [
                            'description' => "Gateway Fee Charge",
                            'quantity' => 1,
                            'unit_price' => $charge,
                        ]
                    ]);
                } catch (\Throwable $invoiceEx) {
                    Log::error('Merchant Deposit Invoice Generation Failed: ' . $invoiceEx->getMessage());
                }

                // Handle bKash Merchant Payment
                if (stripos($gateway->name, 'bkash') !== false) {
                    $bkashService = new \App\Services\Payment\BkashMerchantService($gateway);
                    $payment = $bkashService->createPayment($payable, $trxId);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['payment_id'])) {
                        // Cache paymentID → transaction_id mapping so callback can find the deposit
                        Cache::put("bkash_payment_{$payment['payment_id']}", $trxId, 3600);

                        return response()->json([
                            'status' => true,
                            'payment_id' => $payment['payment_id'],
                            'payment_url' => $payment['payment_url'] ?? null,
                            'requires_redirect' => true,
                            'gateway' => 'bkash',
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'message' => $payment['message'] ?? 'bKash payment creation failed.',
                    ], 500);
                }

                // Handle Nagad Merchant Payment
                if (stripos($gateway->name, 'nagad') !== false) {
                    $nagadService = new \App\Services\Payment\NagadMerchantService($gateway);
                    $payment = $nagadService->initiatePayment($payable, $trxId);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['payment_url'])) {
                        return response()->json([
                            'status' => true,
                            'payment_url' => $payment['payment_url'],
                            'payment_ref_id' => $payment['payment_ref_id'] ?? null,
                            'requires_redirect' => true,
                            'gateway' => 'nagad',
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'message' => $payment['message'] ?? 'Nagad payment creation failed.',
                    ], 500);
                }

                // Handle Rocket Merchant Payment
                if (stripos($gateway->name, 'rocket') !== false) {
                    $rocketService = new \App\Services\Payment\RocketMerchantService($gateway);
                    $payment = $rocketService->initiatePayment($payable, $trxId);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['checkout_url'])) {
                        return response()->json([
                            'status' => true,
                            'checkout_url' => $payment['checkout_url'],
                            'transaction_id' => $payment['transaction_id'] ?? null,
                            'requires_redirect' => true,
                            'gateway' => 'rocket',
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'message' => $payment['message'] ?? 'Rocket payment creation failed.',
                    ], 500);
                }

                // Handle SSLCommerz Payment
                if (stripos($gateway->name, 'sslcommerz') !== false || stripos($gateway->name, 'sslc') !== false) {
                    $sslcService = new \App\Services\Payment\SSLCommerzService($gateway);
                    $payment = $sslcService->initiatePayment($payable, $trxId);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['payment_url'])) {
                        return response()->json([
                            'status' => true,
                            'payment_url' => $payment['payment_url'],
                            'session_key' => $payment['session_key'] ?? null,
                            'requires_redirect' => true,
                            'gateway' => 'sslcommerz',
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'message' => $payment['message'] ?? 'SSLCommerz payment creation failed.',
                    ], 500);
                }

                // Handle EPS Payment
                if (stripos($gateway->name, 'eps') !== false) {
                    $epsService = new \App\Services\Payment\EpsService($gateway);
                    $payment = $epsService->initiatePayment($payable, $trxId);

                    if (isset($payment['status']) && $payment['status'] == true && isset($payment['redirect_url'])) {
                        return response()->json([
                            'status' => true,
                            'redirect_url' => $payment['redirect_url'],
                            'requires_redirect' => true,
                            'gateway' => 'eps',
                        ]);
                    }

                    return response()->json([
                        'status' => false,
                        'message' => $payment['message'] ?? 'EPS payment creation failed.',
                    ], 500);
                }

                // Other merchant gateways - return pending status
                return response()->json([
                    'status' => true, 
                    'message' => 'Deposit requested successfully. Awaiting admin approval.',
                    'transaction_id' => $trxId
                ]);
            }

            // PERSONAL GATEWAY (Manual - requires transaction_id from user)
            $request->validate([
                'transaction_id' => 'required|string',
                'proof_document' => 'nullable|image|mimes:jpg,png,jpeg|max:20480'
            ]);

            // Check for duplicate transaction_id manually (validation rule sometimes missed in race)
            $existingDeposit = \App\Models\Deposit::where('transaction_id', $request->transaction_id)->first();
            if ($existingDeposit) {
                return response()->json([
                    'status' => false,
                    'message' => 'This transaction ID has already been submitted. Please use a different transaction ID.',
                ], 422);
            }

            $proofPath = null;
            if ($request->hasFile('proof_document')) {
                $proofFile = \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('proof_document'));
                $proofPath = $proofFile->store('deposits', 'public');
            }

            $deposit = \App\Models\Deposit::create([
                'user_id' => Auth::id(),
                'gateway_id' => $gateway->id,
                'amount' => $request->amount,
                'charge' => $charge,
                'payable' => $payable,
                'transaction_id' => $request->transaction_id,
                'proof_document' => $proofPath,
                'status' => 'pending'
            ]);

            // Create companion Invoice
            try {
                $invoiceService = app(\App\Services\Billing\InvoiceService::class);
                $user = Auth::user();
                $candidateId = $user->hasRole('candidate') ? $user->id : null;
                $employerId = $user->hasRole('employer') ? $user->id : null;

                $invoiceService->createInvoice([
                    'type' => 'wallet_deposit',
                    'user_id' => $user->id,
                    'candidate_id' => $candidateId,
                    'employer_id' => $employerId,
                    'reference_type' => \App\Models\Deposit::class,
                    'reference_id' => $deposit->id,
                    'currency_code' => 'BDT',
                    'status' => 'pending',
                    'notes' => "Wallet Deposit via {$gateway->display_name}. TrxID: {$request->transaction_id}",
                ], [
                    [
                        'description' => "Wallet Deposit Fund Request",
                        'quantity' => 1,
                        'unit_price' => $request->amount,
                    ],
                    [
                        'description' => "Gateway Fee Charge",
                        'quantity' => 1,
                        'unit_price' => $charge,
                    ]
                ]);
            } catch (\Throwable $invoiceEx) {
                Log::error('Manual Deposit Invoice Generation Failed: ' . $invoiceEx->getMessage());
            }

            return response()->json(['status' => true, 'message' => 'Deposit requested successfully. Awaiting admin approval.']);
            
        } catch (\Throwable $e) {
            Log::error('Deposit Crash: ' . $e->getMessage(), ['user_id' => Auth::id(), 'trace' => $e->getTraceAsString()]);

            $message = 'An unexpected error occurred while processing your deposit. Please try again.';
            $errorCode = 'DEPOSIT_ERROR';

            $msg = strtolower($e->getMessage());

            if (str_contains($msg, 'transaction id has already been taken') || str_contains($msg, 'transaction_id')) {
                $message = 'This transaction ID has already been submitted. Please check and use a unique transaction ID.';
                $errorCode = 'DUPLICATE_TXN';
            } elseif (str_contains($msg, 'gateway')) {
                $message = 'Payment gateway configuration error. Please contact support.';
                $errorCode = 'GATEWAY_ERROR';
            } elseif (str_contains($msg, 'ssl') || str_contains($msg, 'curl') || str_contains($msg, 'connection')) {
                $message = 'Unable to connect to payment gateway. Please try again in a few moments.';
                $errorCode = 'GATEWAY_TIMEOUT';
            } elseif (str_contains($msg, 'validation')) {
                $message = 'Invalid deposit request. Please check the amount and try again.';
                $errorCode = 'VALIDATION_ERROR';
            } elseif (str_contains($msg, 'insufficient') || str_contains($msg, 'balance')) {
                $message = 'Insufficient balance for this transaction.';
                $errorCode = 'INSUFFICIENT_BALANCE';
            }

            if (app()->environment('local', 'development')) {
                $message .= ' (Debug: ' . $e->getMessage() . ')';
            }

            return response()->json([
                'status' => false,
                'message' => $message,
                'error_code' => $errorCode,
            ], 422);
        }
    }

    /**
     * CANDIDATE WALLET
     */
    public function candidateWallet()
    {
        try {
            $userId = Auth::id();
            $wallet = \App\Models\Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'locked_balance' => 0, 'withdrawable_balance' => 0]
            );

            // Auto-heal withdrawable balance if there's a discrepancy
            if ($wallet->withdrawable_balance == 0 && $wallet->balance > 0 && $wallet->locked_balance == 0) {
                $wallet->withdrawable_balance = $wallet->balance;
                $wallet->save();
                Cache::forget("wallet_balance_{$userId}");
            }

            // Cache wallet balance for instant loading
            $cachedBalance = Cache::remember("wallet_balance_{$userId}", 300, function () use ($wallet) {
                return (float) $wallet->balance;
            });
            $wallet->balance = $cachedBalance;

            $transactions = \App\Models\WalletTransaction::where('wallet_id', $wallet->id)->latest()->take(15)->get();
            $withdrawals = \App\Models\Withdrawal::where('user_id', Auth::id())->with('payoutGateway')->latest()->take(10)->get();
            $deposits = \App\Models\Deposit::where('user_id', Auth::id())->with('gateway')->latest()->take(10)->get();

            return response()->json([
                'status' => true,
                'wallet' => $wallet,
                'transactions' => $transactions,
                'withdrawals' => $withdrawals,
                'deposits' => $deposits
            ]);
        } catch (\Throwable $e) {
            Log::error('Candidate Wallet Crash: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again.'], 500);
        }
    }

    /**
     * CANDIDATE WITHDRAWAL
     */
    public function requestWithdrawal(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:500',
                'payment_method' => 'required|string',
                'account_details' => 'required|string',
            ]);

            DB::transaction(function () use ($request) {
                $userId = Auth::id();
                $wallet = \App\Models\Wallet::where('user_id', $userId)->lockForUpdate()->first();

                if (!$wallet || $wallet->balance < $request->amount) {
                    throw new \Exception("Insufficient available balance.");
                }

                $wallet->balance -= $request->amount;
                $wallet->withdrawable_balance = max(0, $wallet->withdrawable_balance - $request->amount);
                $wallet->locked_balance += $request->amount;
                $wallet->save();

                // Invalidate cached balance after wallet mutation
                Cache::forget("wallet_balance_{$userId}");

                \App\Models\Withdrawal::create([
                    'user_id' => $userId,
                    'amount' => $request->amount,
                    'payment_method' => $request->payment_method,
                    'account_details' => $request->account_details,
                    'status' => 'pending'
                ]);

                \App\Models\WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => $request->amount,
                    'reference_type' => 'withdrawal_request',
                    'description' => 'Withdrawal Request: ' . $request->payment_method,
                    'status' => 'pending'
                ]);
            });

            return response()->json(['status' => true, 'message' => 'Withdrawal requested successfully.']);

        } catch (\Throwable $e) {
            Log::error('Withdrawal Request Error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Failed to process withdrawal request.'], 500);
        }
    }

    /**
     * Webhook/Callback from automated payment gateway (OniPay)
     */
    public function oniPayCallback(Request $request)
    {
        try {
            // 1. Webhook signature verification — reject unsigned requests
            $webhookSecret = config('services.onipay.webhook_secret', env('ONIPAY_WEBHOOK_SECRET', ''));
            if ($webhookSecret) {
                $signature = $request->header('X-Webhook-Signature') ?? $request->header('X-Signature');
                $payload = $request->getContent();

                if (!$signature || !hash_equals($signature, hash_hmac('sha256', $payload, $webhookSecret))) {
                    Log::warning('OniPay Callback: Invalid or missing webhook signature');
                    return response()->json(['status' => false, 'message' => 'Invalid signature'], 403);
                }
            }

            Log::info('OniPay Callback received: ', $request->all());

            $transactionId = $request->input('transaction_id');
            $status = $request->input('status'); // e.g. success, completed, paid
            $trxId = $request->input('metadata.trx_id') ?? $request->input('trx_id');

            if (!$trxId) {
                return response()->json(['status' => false, 'message' => 'Missing transaction identifier'], 400);
            }

            // Find the pending deposit
            $deposit = \App\Models\Deposit::where('transaction_id', $trxId)->first();
            if (!$deposit) {
                return response()->json(['status' => false, 'message' => 'Deposit record not found'], 404);
            }

            if ($deposit->status !== 'pending') {
                return response()->json(['status' => true, 'message' => 'Deposit already processed']);
            }

            // Fetch gateway to verify
            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            if (!$gateway) {
                return response()->json(['status' => false, 'message' => 'Gateway not found'], 404);
            }

            // If a transaction_id is provided, verify it directly with the OniPay Service
            if ($transactionId) {
                if (class_exists(\App\Services\Payment\OniPayService::class)) {
                    $verification = \App\Services\Payment\OniPayService::verifyPayment($gateway, $transactionId);
                    Log::info('OniPay verification response: ', $verification ?? []);
                    
                    if (isset($verification['status']) && ($verification['status'] === true || $verification['status'] === 'success' || (isset($verification['data']['status']) && $verification['data']['status'] === 'success'))) {
                        $status = 'success';
                    } else {
                        $status = 'failed';
                    }
                }
            }

            if ($status === 'success' || $status === 'completed' || $status === 'paid') {
                // Mark Deposit as Approved - triggers the Deposit updating model event
                $deposit->update(['status' => 'approved']);

                // Invalidate cached wallet balance for the user
                Cache::forget("wallet_balance_{$deposit->user_id}");

                return response()->json(['status' => true, 'message' => 'Deposit approved and wallet credited']);
            } else {
                // Mark Deposit as Rejected - triggers the Deposit updating model event (voids invoice)
                $deposit->update(['status' => 'rejected']);

                return response()->json(['status' => false, 'message' => 'Deposit rejected due to payment status']);
            }

        } catch (\Throwable $e) {
            Log::error('OniPay Callback Crash: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to process payment callback.'], 500);
        }
    }

    /**
     * Execute bKash Payment after user authorization
     */
    public function executeBkashPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|string',
                'transaction_id' => 'required|string',
            ]);

            $deposit = \App\Models\Deposit::where('transaction_id', $request->transaction_id)->first();

            if (!$deposit) {
                return response()->json(['status' => false, 'message' => 'Deposit record not found'], 404);
            }

            if ($deposit->status !== 'pending') {
                return response()->json(['status' => true, 'message' => 'Deposit already processed']);
            }

            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $bkashService = new \App\Services\Payment\BkashMerchantService($gateway);
            $result = $bkashService->executePayment($request->payment_id);

            if (isset($result['status']) && $result['status'] == true && isset($result['success']) && $result['success']) {
                $deposit->update([
                    'status' => 'approved',
                    'admin_feedback' => json_encode([
                        'bkash_trx_id' => $result['trx_id'],
                        'bkash_payment_id' => $result['payment_id'],
                        'payer_reference' => $result['payer_reference'],
                    ]),
                ]);

                Cache::forget("wallet_balance_{$deposit->user_id}");

                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful! Wallet has been credited.',
                    'trx_id' => $result['trx_id'],
                ]);
            } else {
                $deposit->update([
                    'status' => 'rejected',
                    'admin_feedback' => $result['message'] ?? 'Payment execution failed',
                ]);

                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Payment failed. Please try again.',
                ], 400);
            }

        } catch (\Throwable $e) {
            Log::error('bKash Execute Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment processing error.'], 500);
        }
    }

    /**
     * bKash Payment Callback — called by bKash after user authorization
     */
    public function bkashCallback(Request $request)
    {
        try {
            $paymentID = $request->query('paymentID');
            $status = $request->query('status');

            if (!$paymentID) {
                return redirect(url('/dashboard/wallet?payment=error'));
            }

            // Find the deposit via cached paymentID → transaction_id mapping
            $trxId = Cache::get("bkash_payment_{$paymentID}");

            if (!$trxId) {
                // Fallback: look for most recent pending bKash deposit
                $gatewayIds = \App\Models\Gateway::where('name', 'like', '%bkash%')->pluck('id');
                $deposit = \App\Models\Deposit::whereIn('gateway_id', $gatewayIds)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
            } else {
                $deposit = \App\Models\Deposit::where('transaction_id', $trxId)->first();
            }

            if (!$deposit || $deposit->status !== 'pending') {
                return redirect(url('/dashboard/wallet?payment=already_processed'));
            }

            // Determine frontend redirect URL based on user role
            $user = \App\Models\User::find($deposit->user_id);
            $frontendUrl = $user && $user->hasRole('employer')
                ? url('/employer/wallet')
                : url('/dashboard/wallet');

            // Execute the payment via bKash API
            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $bkashService = new \App\Services\Payment\BkashMerchantService($gateway);
            $result = $bkashService->executePayment($paymentID);

            if (isset($result['status']) && $result['status'] == true && isset($result['success']) && $result['success']) {
                $deposit->update([
                    'status' => 'approved',
                    'admin_feedback' => json_encode([
                        'bkash_trx_id' => $result['trx_id'],
                        'bkash_payment_id' => $result['payment_id'],
                        'payer_reference' => $result['payer_reference'],
                    ]),
                ]);

                Cache::forget("wallet_balance_{$deposit->user_id}");
                Cache::forget("bkash_payment_{$paymentID}");

                return redirect($frontendUrl . '?payment=success');
            } else {
                $deposit->update([
                    'status' => 'rejected',
                    'admin_feedback' => $result['message'] ?? 'Payment execution failed',
                ]);

                Cache::forget("bkash_payment_{$paymentID}");

                return redirect($frontendUrl . '?payment=failed');
            }

        } catch (\Throwable $e) {
            Log::error('bKash Callback Error: ' . $e->getMessage());
            return redirect(url('/dashboard/wallet?payment=error'));
        }
    }

    /**
     * Execute Nagad Payment after user authorization
     */
    public function executeNagadPayment(Request $request)
    {
        try {
            $request->validate([
                'payment_ref_id' => 'required|string',
                'transaction_id' => 'required|string',
            ]);

            $deposit = \App\Models\Deposit::where('transaction_id', $request->transaction_id)->first();

            if (!$deposit) {
                return response()->json(['status' => false, 'message' => 'Deposit record not found'], 404);
            }

            if ($deposit->status !== 'pending') {
                return response()->json(['status' => true, 'message' => 'Deposit already processed']);
            }

            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $nagadService = new \App\Services\Payment\NagadMerchantService($gateway);
            $result = $nagadService->verifyPayment($request->payment_ref_id);

            if (isset($result['status']) && $result['status'] == true) {
                $deposit->update([
                    'status' => 'approved',
                    'admin_feedback' => json_encode([
                        'nagad_payment_ref_id' => $result['payment_ref_id'],
                        'nagad_order_id' => $result['order_id'],
                        'client_mobile' => $result['client_mobile'],
                    ]),
                ]);

                Cache::forget("wallet_balance_{$deposit->user_id}");

                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful! Wallet has been credited.',
                ]);
            } else {
                $deposit->update([
                    'status' => 'rejected',
                    'admin_feedback' => $result['message'] ?? 'Payment verification failed',
                ]);

                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Payment failed. Please try again.',
                ], 400);
            }

        } catch (\Throwable $e) {
            Log::error('Nagad Execute Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment processing error.'], 500);
        }
    }

    /**
     * Execute Rocket Payment after user authorization
     */
    public function executeRocketPayment(Request $request)
    {
        try {
            $request->validate([
                'transaction_id' => 'required|string',
            ]);

            $deposit = \App\Models\Deposit::where('transaction_id', $request->transaction_id)->first();

            if (!$deposit) {
                return response()->json(['status' => false, 'message' => 'Deposit record not found'], 404);
            }

            if ($deposit->status !== 'pending') {
                return response()->json(['status' => true, 'message' => 'Deposit already processed']);
            }

            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $rocketService = new \App\Services\Payment\RocketMerchantService($gateway);
            $result = $rocketService->verifyPayment($request->transaction_id);

            if (isset($result['status']) && $result['status'] == true) {
                $deposit->update([
                    'status' => 'approved',
                    'admin_feedback' => json_encode([
                        'rocket_transaction_id' => $result['transaction_id'],
                        'rocket_order_id' => $result['order_id'],
                        'mobile_number' => $result['mobile_number'],
                    ]),
                ]);

                Cache::forget("wallet_balance_{$deposit->user_id}");

                return response()->json([
                    'status' => true,
                    'message' => 'Payment successful! Wallet has been credited.',
                ]);
            } else {
                $deposit->update([
                    'status' => 'rejected',
                    'admin_feedback' => $result['message'] ?? 'Payment verification failed',
                ]);

                return response()->json([
                    'status' => false,
                    'message' => $result['message'] ?? 'Payment failed. Please try again.',
                ], 400);
            }

        } catch (\Throwable $e) {
            Log::error('Rocket Execute Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment processing error.'], 500);
        }
    }

    /**
     * SSLCommerz Payment Callback
     */
    public function sslCommerzCallback(Request $request)
    {
        try {
            $tranId = $request->input('tran_id');

            if (!$tranId) {
                return redirect(config('services.sslcommerz.callback_url', '/'));
            }

            $deposit = \App\Models\Deposit::where('transaction_id', $tranId)->first();

            if (!$deposit || $deposit->status !== 'pending') {
                return redirect(config('services.sslcommerz.callback_url', '/'));
            }

            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $sslcService = new \App\Services\Payment\SSLCommerzService($gateway);
            $result = $sslcService->validatePayment($request->all());

            if (isset($result['status']) && $result['status'] == true) {
                $deposit->update([
                    'status' => 'approved',
                    'admin_feedback' => json_encode([
                        'sslc_tran_id' => $result['tran_id'],
                        'sslc_val_id' => $result['val_id'],
                        'sslc_bank_tran_id' => $result['bank_tran_id'],
                        'sslc_card_type' => $result['card_type'],
                    ]),
                ]);

                Cache::forget("wallet_balance_{$deposit->user_id}");

                return redirect(url('/wallet?payment=success'));
            } else {
                $deposit->update([
                    'status' => 'rejected',
                    'admin_feedback' => $result['message'] ?? 'Payment validation failed',
                ]);

                return redirect(url('/wallet?payment=failed'));
            }

        } catch (\Throwable $e) {
            Log::error('SSLCommerz Callback Error: ' . $e->getMessage());
            return redirect(url('/wallet?payment=error'));
        }
    }

    /**
     * EPS Payment Callback — handles redirect from EPS payment page
     * Called by frontend callback page via POST, or directly by EPS via GET
     */
    public function epsCallback(Request $request)
    {
        try {
            $merchantTransactionId = $request->input('merchantTransactionId')
                ?? $request->input('MerchantTransactionId')
                ?? $request->input('merchant_transaction_id')
                ?? $request->input('CustomerOrderId')
                ?? $request->input('customerOrderId')
                ?? $request->input('transaction_id')
                ?? $request->input('transactionId')
                ?? $request->query('merchantTransactionId')
                ?? $request->query('MerchantTransactionId')
                ?? $request->query('transaction_id');

            if (!$merchantTransactionId) {
                // Fallback: look for most recent pending EPS deposit
                $gatewayIds = \App\Models\Gateway::where('name', 'like', '%eps%')->pluck('id');
                $deposit = \App\Models\Deposit::whereIn('gateway_id', $gatewayIds)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();
                if ($deposit) {
                    $merchantTransactionId = $deposit->transaction_id;
                }
            }

            if (!$merchantTransactionId) {
                return redirect(url('/dashboard/wallet?payment=error'));
            }

            $deposit = \App\Models\Deposit::where('transaction_id', $merchantTransactionId)->first();

            if (!$deposit || $deposit->status !== 'pending') {
                // Already processed — redirect to frontend
                return redirect(url('/dashboard/wallet?payment=already_processed'));
            }

            $gateway = \App\Models\Gateway::find($deposit->gateway_id);
            $epsService = new \App\Services\Payment\EpsService($gateway);
            $result = $epsService->checkTransactionStatus($merchantTransactionId);

            if (isset($result['status']) && $result['status'] == true) {
                $epsStatus = strtolower($result['eps_status'] ?? '');

                if (in_array($epsStatus, ['completed', 'success', 'paid', 'approved'])) {
                    $deposit->update([
                        'status' => 'approved',
                        'admin_feedback' => json_encode([
                            'eps_transaction_id' => $result['transaction_id'] ?? $merchantTransactionId,
                            'eps_status' => $result['eps_status'],
                            'eps_total_amount' => $result['total_amount'],
                        ]),
                    ]);

                    Cache::forget("wallet_balance_{$deposit->user_id}");

                    // Determine frontend URL for redirect
                    $user = \App\Models\User::find($deposit->user_id);
                    $frontendBase = env('FRONTEND_URL', 'https://ejobs.bd');
                    $frontendUrl = $user && $user->hasRole('employer')
                        ? $frontendBase . '/employer/wallet'
                        : $frontendBase . '/dashboard/wallet';

                    // If frontend called us (expects JSON), return JSON
                    if ($request->expectsJson() || $request->isMethod('post')) {
                        return response()->json([
                            'status' => true,
                            'payment' => 'success',
                            'message' => 'Payment successful! Your wallet has been credited.',
                            'redirect_url' => $frontendUrl,
                        ]);
                    }

                    // Direct GET from EPS — redirect to frontend
                    return redirect($frontendUrl . '?payment=success');
                } else {
                    $deposit->update([
                        'status' => 'rejected',
                        'admin_feedback' => json_encode([
                            'eps_status' => $result['eps_status'],
                            'message' => 'Payment not completed. Status: ' . $result['eps_status'],
                        ]),
                    ]);

                    $user = \App\Models\User::find($deposit->user_id);
                    $frontendBase = env('FRONTEND_URL', 'https://ejobs.bd');
                    $frontendUrl = $user && $user->hasRole('employer')
                        ? $frontendBase . '/employer/wallet'
                        : $frontendBase . '/dashboard/wallet';

                    if ($request->expectsJson() || $request->isMethod('post')) {
                        return response()->json([
                            'status' => false,
                            'payment' => 'failed',
                            'message' => 'Payment not completed. Status: ' . $result['eps_status'],
                            'redirect_url' => $frontendUrl,
                        ]);
                    }

                    return redirect($frontendUrl . '?payment=failed');
                }
            }

            $deposit->update([
                'status' => 'rejected',
                'admin_feedback' => $result['message'] ?? 'EPS status check failed',
            ]);

            $user = \App\Models\User::find($deposit->user_id);
            $frontendBase = env('FRONTEND_URL', 'https://ejobs.bd');
            $frontendUrl = $user && $user->hasRole('employer')
                ? $frontendBase . '/employer/wallet'
                : $frontendBase . '/dashboard/wallet';

            if ($request->expectsJson() || $request->isMethod('post')) {
                return response()->json([
                    'status' => false,
                    'payment' => 'failed',
                    'message' => $result['message'] ?? 'EPS status check failed',
                    'redirect_url' => $frontendUrl,
                ]);
            }

            return redirect($frontendUrl . '?payment=failed');

        } catch (\Throwable $e) {
            Log::error('EPS Callback Error: ' . $e->getMessage());

            // Try to find user from merchantTransactionId in the request
            $frontendBase = env('FRONTEND_URL', 'https://ejobs.bd');
            $frontendUrl = $frontendBase . '/dashboard/wallet';
            if ($merchantTransactionId) {
                $depositForError = \App\Models\Deposit::where('transaction_id', $merchantTransactionId)->first();
                if ($depositForError) {
                    $userForError = \App\Models\User::find($depositForError->user_id);
                    $frontendUrl = $userForError && $userForError->hasRole('employer')
                        ? $frontendBase . '/employer/wallet'
                        : $frontendBase . '/dashboard/wallet';
                }
            }

            if ($request->expectsJson() || $request->isMethod('post')) {
                return response()->json([
                    'status' => false,
                    'payment' => 'error',
                    'message' => 'Payment processing error.',
                    'redirect_url' => $frontendUrl . '?payment=error',
                ], 500);
            }

            return redirect($frontendUrl . '?payment=error');
        }
    }
}