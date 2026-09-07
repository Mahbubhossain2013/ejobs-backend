<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BkashMerchantService
{
    private $baseUrl;
    private $username;
    private $password;
    private $appKey;
    private $appSecret;

    public function __construct($gateway = null)
    {
        if ($gateway) {
            $this->username = $gateway->username;
            $this->password = $gateway->password;
            $this->appKey = $gateway->app_key;
            $this->appSecret = $gateway->app_secret;
            $sandbox = $gateway->is_sandbox ?? true;
        } else {
            $this->username = config('services.bkash.username', '');
            $this->password = config('services.bkash.password', '');
            $this->appKey = config('services.bkash.app_key', '');
            $this->appSecret = config('services.bkash.app_secret', '');
            $sandbox = config('services.bkash.sandbox', true);
        }
        
        $this->baseUrl = $sandbox
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized';
    }

    /**
     * Get access token from bKash
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'bkash_access_token';
        $token = Cache::get($cacheKey);

        if ($token) {
            return $token;
        }

        try {
            $response = Http::withHeaders([
                'username' => $this->username,
                'password' => $this->password,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/checkout/token/grant", [
                'app_key' => $this->appKey,
                'app_secret' => $this->appSecret,
            ]);

            if ($response->successful() && isset($response['id_token'])) {
                $token = $response['id_token'];
                // Cache for 50 minutes (token expires in 60 min)
                Cache::put($cacheKey, $token, 3000);
                return $token;
            }

            Log::error('bKash Token Grant Failed', $response->json());
            return null;
        } catch (\Throwable $e) {
            Log::error('bKash Token Grant Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a payment session
     */
    public function createPayment(float $amount, string $trxId, ?string $payerReference = null, ?string $callbackURL = null): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['status' => false, 'message' => 'Failed to get bKash access token'];
        }

        $callbackUrl = $callbackURL ?? config('services.bkash.callback_url', url('/api/payment/bkash/callback'));

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ])->post("{$this->baseUrl}/checkout/create", [
                'mode' => '0011',
                'payerReference' => $payerReference ?? '01770618575',
                'callbackURL' => $callbackUrl,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => 'BDT',
                'intent' => 'sale',
                'merchantInvoiceNumber' => $trxId,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['paymentID'])) {
                return [
                    'status' => true,
                    'payment_id' => $data['paymentID'],
                    'payment_url' => $data['bkashURL'] ?? null,
                    'success' => $data['success'] ?? false,
                    'response_message' => $data['statusMessage'] ?? '',
                ];
            }

            Log::error('bKash Create Payment Failed', $data);
            return [
                'status' => false,
                'message' => $data['statusMessage'] ?? $data['errorMessage'] ?? 'Payment creation failed',
            ];
        } catch (\Throwable $e) {
            Log::error('bKash Create Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment creation error: ' . $e->getMessage()];
        }
    }

    /**
     * Execute a payment after user authorization
     */
    public function executePayment(string $paymentId): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['status' => false, 'message' => 'Failed to get bKash access token'];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ])->post("{$this->baseUrl}/checkout/execute", [
                'paymentID' => $paymentId,
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['paymentID'])) {
                return [
                    'status' => true,
                    'payment_id' => $data['paymentID'],
                    'trx_id' => $data['trxID'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency'] ?? 'BDT',
                    'payer_reference' => $data['payerReference'] ?? '',
                    'payment_execute_status' => $data['paymentExecuteStatus'] ?? '',
                    'success' => $data['success'] ?? false,
                    'status_message' => $data['statusMessage'] ?? '',
                ];
            }

            Log::error('bKash Execute Payment Failed', $data);
            return [
                'status' => false,
                'message' => $data['statusMessage'] ?? $data['errorMessage'] ?? 'Payment execution failed',
            ];
        } catch (\Throwable $e) {
            Log::error('bKash Execute Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment execution error: ' . $e->getMessage()];
        }
    }

    /**
     * Query payment status
     */
    public function queryPayment(string $paymentId): ?array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'X-APP-Key' => $this->appKey,
            ])->post("{$this->baseUrl}/checkout/payment/status/{$paymentId}");

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('bKash Query Payment Error: ' . $e->getMessage());
            return null;
        }
    }
}
