<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSLCommerzService
{
    private $storeId;
    private $storePassword;
    private $baseUrl;
    private $sandbox;

    public function __construct($gateway = null)
    {
        if ($gateway) {
            $this->storeId = $gateway->sslc_store_id ?? config('services.sslcommerz.store_id', '');
            $this->storePassword = $gateway->sslc_store_password ?? config('services.sslcommerz.store_password', '');
            $this->sandbox = $gateway->is_sandbox ?? true;
        } else {
            $this->storeId = config('services.sslcommerz.store_id', '');
            $this->storePassword = config('services.sslcommerz.store_password', '');
            $this->sandbox = config('services.sslcommerz.sandbox', true);
        }

        $this->baseUrl = $this->sandbox
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Initiate a payment session
     */
    public function initiatePayment(float $amount, string $trxId, ?string $callbackURL = null): ?array
    {
        try {
            $callbackUrl = $callbackURL ?? config('services.sslcommerz.callback_url', url('/api/payment/sslcommerz/callback'));

            $postData = [
                'store_id' => $this->storeId,
                'store_passwd' => $this->storePassword,
                'total_amount' => number_format($amount, 2, '.', ''),
                'currency' => 'BDT',
                'tran_id' => $trxId,
                'success_url' => $callbackUrl,
                'fail_url' => $callbackUrl,
                'cancel_url' => $callbackUrl,
                'ipn_url' => $callbackUrl,
                'product_category' => 'Wallet Deposit',
                'product_name' => 'Wallet Fund',
                'product_profile' => 'general',

                'cus_name' => 'Customer',
                'cus_email' => 'customer@example.com',
                'cus_add1' => 'Dhaka',
                'cus_city' => 'Dhaka',
                'cus_country' => 'Bangladesh',
                'cus_phone' => '01700000000',

                'shipping_method' => 'NO',
                'num_of_item' => 1,
            ];

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->asForm()
                ->post("{$this->baseUrl}/gwprocess/v4/api.php", $postData);

            $data = $response->json();

            if (isset($data['GatewayPageURL']) && !empty($data['GatewayPageURL'])) {
                return [
                    'status' => true,
                    'payment_url' => $data['GatewayPageURL'],
                    'session_key' => $data['sessionkey'] ?? null,
                ];
            }

            Log::error('SSLCommerz Initiate Payment Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['failedreason'] ?? 'Payment initiation failed',
            ];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz Initiate Payment Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment initiation error: ' . $e->getMessage()];
        }
    }

    /**
     * Validate a payment after redirect
     */
    public function validatePayment(array $postData): ?array
    {
        try {
            $valId = $postData['val_id'] ?? '';

            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get("{$this->baseUrl}/validator/api/validationserverAPI.php", [
                    'val_id' => $valId,
                    'store_id' => $this->storeId,
                    'store_passwd' => $this->storePassword,
                    'v' => 1,
                    'format' => 'json',
                ]);

            $data = $response->json();

            if (isset($data['status']) && in_array($data['status'], ['VALID', 'VALIDATED'])) {
                return [
                    'status' => true,
                    'tran_id' => $data['tran_id'] ?? null,
                    'amount' => $data['amount'] ?? null,
                    'currency' => $data['currency_type'] ?? 'BDT',
                    'card_type' => $data['card_type'] ?? '',
                    'bank_tran_id' => $data['bank_tran_id'] ?? '',
                    'val_id' => $data['val_id'] ?? '',
                    'status_message' => $data['status'] ?? '',
                ];
            }

            Log::error('SSLCommerz Validation Failed', $data ?? []);
            return [
                'status' => false,
                'message' => $data['error'] ?? 'Payment validation failed',
            ];
        } catch (\Throwable $e) {
            Log::error('SSLCommerz Validation Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Payment validation error: ' . $e->getMessage()];
        }
    }

    /**
     * Verify hash signature from callback
     */
    public function verifyHash(array $postData): bool
    {
        if (empty($postData['verify_sign']) || empty($postData['verify_key'])) {
            return false;
        }

        $verifyKeys = explode(',', $postData['verify_key']);
        $newData = [];

        foreach ($verifyKeys as $key) {
            $newData[$key] = $postData[$key] ?? '';
        }

        $newData['store_passwd'] = md5($this->storePassword);
        ksort($newData);

        $hashString = '';
        foreach ($newData as $key => $value) {
            $hashString .= $key . '=' . $value . '&';
        }
        $hashString = rtrim($hashString, '&');

        return md5($hashString) === $postData['verify_sign'];
    }
}
