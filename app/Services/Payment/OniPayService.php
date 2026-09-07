<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OniPayService
{
    public static function createOrder($gateway, $data)
    {
        // Fallback to localhost if FRONTEND_URL is not set in .env
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        $response = Http::withHeaders([
            'API-KEY' => $gateway->api_key,
            'Content-Type' => 'application/json',
        ])->post('https://pay.onipay.xyz/api/payment/create', [
            'amount' => $data['amount'],
            // Redirect user back to their wallet on success or cancel
            'success_url' => $frontendUrl . '/employer/wallet?payment=success',
            'cancel_url' => $frontendUrl . '/employer/wallet?payment=cancel',
            'cus_name' => $data['name'],
            'cus_email' => $data['email'],
            'metadata' => [
                'phone' => $data['phone'],
                'trx_id' => $data['trx_id']
            ]
        ]);

        $result = $response->json();

        // Log error if gateway rejects the API key or data
        if (!$response->successful() || !isset($result['status']) || $result['status'] == false) {
            Log::error('OniPay Create Error: ', $result ?? []);
        }

        return $result;
    }

    public static function verifyPayment($gateway, $transactionId)
    {
        $response = Http::withHeaders([
            'API-KEY' => $gateway->api_key,
            'Content-Type' => 'application/json',
        ])->post('https://pay.onipay.xyz/api/payment/verify', [
            'transaction_id' => $transactionId
        ]);

        return $response->json();
    }
}