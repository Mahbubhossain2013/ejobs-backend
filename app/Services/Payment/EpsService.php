<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EpsService
{
    private $baseUrl;
    private $merchantId;
    private $storeId;
    private $hashKey;
    private $userName;
    private $password;
    private $isSandbox;

    public function __construct($gateway = null)
    {
        if ($gateway) {
            $this->merchantId = $gateway->eps_merchant_id ?? '';
            $this->storeId    = $gateway->eps_store_id ?? '';
            $this->hashKey    = $gateway->eps_hash_key ?? '';
            $this->userName   = $gateway->eps_user_name ?? '';
            $this->password   = $gateway->eps_password ?? '';
            $this->isSandbox  = $gateway->is_sandbox ?? true;
        } else {
            $this->merchantId = config('services.eps.merchant_id', '');
            $this->storeId    = config('services.eps.store_id', '');
            $this->hashKey    = config('services.eps.hash_key', '');
            $this->userName   = config('services.eps.user_name', '');
            $this->password   = config('services.eps.password', '');
            $this->isSandbox  = config('services.eps.sandbox', true);
        }

        $this->baseUrl = $this->isSandbox
            ? 'https://sandboxpgapi.eps.com.bd/v1'
            : 'https://pgapi.eps.com.bd/v1';
    }

    /**
     * Generate HMAC-SHA512 hash, base64 encoded
     * EPS docs: "Encode HashKey using UTF8" → use the string directly
     */
    private function generateHash(string $data): string
    {
        return base64_encode(hash_hmac('sha512', $data, $this->hashKey, true));
    }

    /**
     * Get Bearer token from EPS Auth API
     * Cached for ~50 minutes (token lives ~60 min)
     */
    public function getToken(): ?string
    {
        $cacheKey = 'eps_access_token';
        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        try {
            $xHash = $this->generateHash($this->userName);

            $response = Http::withHeaders([
                'x-hash' => $xHash,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/Auth/GetToken", [
                'userName' => $this->userName,
                'password' => $this->password,
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data['token'])) {
                // Cache for 50 minutes
                Cache::put($cacheKey, $data['token'], 3000);
                return $data['token'];
            }

            Log::error('EPS GetToken Failed', $data ?? []);
            return null;
        } catch (\Throwable $e) {
            Log::error('EPS GetToken Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate a payment — returns redirect URL
     */
    public function initiatePayment(float $amount, string $trxId, ?string $callbackUrl = null): ?array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['status' => false, 'message' => 'EPS authentication failed. Please check your API username and password.'];
            }

            $callbackURL = $callbackUrl ?? config('services.eps.callback_url') ?: url('/payment/eps/callback');

            $xHash = $this->generateHash($trxId);

            $body = [
                'merchantId'              => $this->merchantId,
                'storeId'                 => $this->storeId,
                'CustomerOrderId'         => $trxId,
                'merchantTransactionId'   => $trxId,
                'transactionTypeId'       => 10,
                'financialEntityId'       => 0,
                'transitionStatusId'      => 0,
                'totalAmount'             => number_format($amount, 2, '.', ''),
                'ipAddress'               => request()->ip() ?? '127.0.0.1',
                'version'                 => '1.0',
                'successUrl'              => $callbackURL,
                'failUrl'                 => $callbackURL,
                'cancelUrl'               => $callbackURL,
                'customerName'            => 'Customer',
                'customerEmail'           => 'customer@example.com',
                'customerAddress'         => 'Dhaka',
                'customerCity'            => 'Dhaka',
                'customerCountry'         => 'Bangladesh',
                'customerPhone'           => '01700000000',
                'productName'             => 'Wallet Deposit',
                'productProfile'          => 'general',
                'noOfItem'                => '1',
                'ProductList'             => [
                    [
                        'ProductName'    => 'Wallet Deposit',
                        'NoOfItem'       => '1',
                        'ProductProfile' => 'general',
                        'ProductCategory'=> 'Wallet',
                        'ProductPrice'   => number_format($amount, 2, '.', ''),
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'x-hash'        => $xHash,
                'Content-Type'  => 'application/json',
            ])->post("{$this->baseUrl}/EPSEngine/InitializeEPS", $body);

            $data = $response->json();

            if ($response->successful() && !empty($data['RedirectURL'])) {
                return [
                    'status'       => true,
                    'redirect_url' => $data['RedirectURL'],
                    'transaction_id' => $data['TransactionId'] ?? null,
                ];
            }

            $errorMsg = ($data['ErrorMessage'] ?? $data['errorMessage'] ?? null) ?: 'Payment initiation failed';
            $errorCode = $data['ErrorCode'] ?? $data['errorCode'] ?? 'N/A';
            Log::error('EPS Initiate Payment Failed', [
                'status_code' => $response->status(),
                'error_code' => $errorCode,
                'error_message' => $errorMsg,
                'response' => $data ?? [],
                'body' => $body,
            ]);

            return [
                'status'  => false,
                'message' => "EPS Error [{$errorCode}]: {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error('EPS Initiate Payment Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => false, 'message' => 'Payment initiation error: ' . $e->getMessage()];
        }
    }

    /**
     * Check merchant transaction status
     */
    public function checkTransactionStatus(string $merchantTransactionId): ?array
    {
        try {
            $token = $this->getToken();
            if (!$token) {
                return ['status' => false, 'message' => 'Failed to get EPS access token'];
            }

            $xHash = $this->generateHash($merchantTransactionId);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'x-hash'        => $xHash,
            ])->get("{$this->baseUrl}/EPSEngine/CheckMerchantTransactionStatus", [
                'merchantTransactionId' => $merchantTransactionId,
            ]);

            $data = $response->json();

            if ($response->successful() && !empty($data['Status'])) {
                return [
                    'status'           => true,
                    'eps_status'       => $data['Status'],
                    'total_amount'     => $data['TotalAmount'] ?? null,
                    'transaction_id'   => $data['TransactionId'] ?? null,
                    'transaction_date' => $data['TransactionDate'] ?? null,
                ];
            }

            Log::error('EPS Check Status Failed', $data ?? []);
            return [
                'status'  => false,
                'message' => $data['ErrorMessage'] ?? 'Status check failed',
            ];
        } catch (\Throwable $e) {
            Log::error('EPS Check Status Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Status check error: ' . $e->getMessage()];
        }
    }
}
