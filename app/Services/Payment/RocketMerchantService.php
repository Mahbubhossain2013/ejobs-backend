<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RocketMerchantService
{
    private $baseUrl;
    private $merchantId;
    private $apiPassword;
    private $apiKey;

    public function __construct($gateway = null)
    {
        if ($gateway) {
            $this->merchantId = $gateway->rocket_merchant_id ?? $gateway->username;
            $this->apiPassword = $gateway->rocket_api_password ?? $gateway->password;
            $this->apiKey = $gateway->rocket_api_key ?? $gateway->app_key;
            $sandbox = $gateway->is_sandbox ?? true;
        } else {
            $sandbox = config('services.rocket.sandbox', true);
        }

        $this->baseUrl = $sandbox
            ? 'https://sandbox.shurjopay.com/rocket'
            : 'https://shurjopay.com/rocket';
    }

    /**
     * Initiate a payment
     */
    public function initiatePayment(float $amount, string $invoice): ?array
    {
        try {
            $dateTime = date('Y-m-d H:i:s');
            $orderId = $invoice;

            // Create signature
            $signatureData = $this->merchantId . $orderId . number_format($amount, 2, '.', '') . $dateTime;
            $signature = hash_hmac('sha256', $signatureData, $this->apiKey);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey,
            ])->post("{$this->baseUrl}/api/checkout/payment", [
                'store_id' => $this->merchantId,
                'order_id' => $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => 'BDT',
                'date_time' => $dateTime,
                'signature' => $signature,
                'callback_url' => route('payment.rocket.callback'),
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['checkout_url'])) {
                return [
                    'status' => true,
                    'checkout_url' => $data['checkout_url'],
                    'order_id' => $orderId,
                    'transaction_id' => $data['transaction_id'] ?? null,
                ];
            }

            Log::error('Rocket Initiate Payment Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['message'] ?? 'Payment initiation failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Rocket Initiate Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment initiation error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $transactionId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->apiKey,
            ])->get("{$this->baseUrl}/api/checkout/status/{$transactionId}");

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'Success') {
                return [
                    'status' => true,
                    'order_id' => $data['order_id'] ?? null,
                    'transaction_id' => $data['transaction_id'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'mobile_number' => $data['mobile_number'] ?? '',
                    'status_message' => $data['message'] ?? '',
                ];
            }

            Log::error('Rocket Verify Payment Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['message'] ?? 'Payment verification failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Rocket Verify Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment verification error'];
        }
    }
}
