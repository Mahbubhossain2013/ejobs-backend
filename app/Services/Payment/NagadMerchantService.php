<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NagadMerchantService
{
    private $baseUrl;
    private $merchantId;
    private $privateKey;
    private $pgPublicKey;

    public function __construct($gateway = null)
    {
        if ($gateway) {
            $this->merchantId = $gateway->nagad_merchant_id ?? $gateway->username;
            $this->privateKey = $gateway->nagad_private_key ?? $gateway->password;
            $this->pgPublicKey = $gateway->nagad_pg_public_key ?? $gateway->app_key;
            $sandbox = $gateway->is_sandbox ?? true;
        } else {
            $sandbox = config('services.nagad.sandbox', true);
        }

        $this->baseUrl = $sandbox
            ? 'https://sandbox.mynagad.com/api/df'
            : 'https://mynagad.com/api/df';
    }

    /**
     * Initiate a payment
     */
    public function initiatePayment(float $amount, string $invoice): ?array
    {
        try {
            $dateTime = date('YmdHis');
            $orderId = $invoice . '_' . $dateTime;

            $paymentData = [
                'amount' => number_format($amount, 2, '.', ''),
                'invoice' => $invoice,
                'currency' => 'BDT',
                'merchantCallback' => route('payment.nagad.callback'),
            ];

            // Create signature
            $signature = $this->createSignature($paymentData);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->merchantId . ':' . $signature,
            ])->post("{$this->baseUrl}/checkout/initiate", [
                'merchantId' => $this->merchantId,
                'orderId' => $orderId,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => 'BDT',
                'dateTime' => $dateTime,
                'signature' => $signature,
                'callbackURL' => route('payment.nagad.callback'),
                'merchantCallback' => route('payment.nagad.callback'),
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['paymentUrl'])) {
                return [
                    'status' => true,
                    'payment_url' => $data['paymentUrl'],
                    'order_id' => $orderId,
                    'payment_ref_id' => $data['paymentRefId'] ?? null,
                ];
            }

            Log::error('Nagad Initiate Payment Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['message'] ?? 'Payment initiation failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Nagad Initiate Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment initiation error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify a payment
     */
    public function verifyPayment(string $paymentRefId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->merchantId,
            ])->get("{$this->baseUrl}/verify/$paymentRefId");

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'Success') {
                return [
                    'status' => true,
                    'order_id' => $data['orderId'] ?? null,
                    'payment_ref_id' => $data['paymentRefId'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'client_mobile' => $data['clientMobileNo'] ?? '',
                    'status_message' => $data['message'] ?? '',
                ];
            }

            Log::error('Nagad Verify Payment Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['message'] ?? 'Payment verification failed',
            ];
        } catch (\Throwable $e) {
            Log::error('Nagad Verify Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment verification error'];
        }
    }

    /**
     * Create signature for payment request
     */
    private function createSignature(array $data): string
    {
        $dataString = http_build_query($data);
        $privateKey = openssl_pkey_get_private($this->privateKey);

        if (!$privateKey) {
            return '';
        }

        openssl_sign($dataString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($privateKey);

        return base64_encode($signature);
    }
}
