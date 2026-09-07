<?php

namespace App\Services\Notification;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send SMS using the active driver configured in settings.
     */
    public function sendSms(string $number, string $message): array
    {
        try {
            $settings = Setting::all()->pluck('value', 'key')->toArray();

            $enabled = $settings['sms_enabled'] ?? '0';
            if ($enabled !== '1' && $enabled !== 'true') {
                Log::info("SMS sending is disabled globally. Logged message to {$number}: {$message}");
                return ['status' => 'disabled', 'message' => 'SMS is disabled globally.'];
            }

            $driver = $settings['sms_driver'] ?? 'bulksmsbd';
            $apiKey = $settings['sms_api_token'] ?? '';
            $senderId = $settings['sms_sender_id'] ?? '';

            // Clean number format to match BulkSMSBD guidelines (11 digits starting with 01)
            $cleanedNumber = $this->cleanPhoneNumber($number);

            if (strlen($cleanedNumber) !== 11 || !str_starts_with($cleanedNumber, '01')) {
                Log::warning("Skipped SMS: Cleaned phone number '{$cleanedNumber}' (from '{$number}') is invalid.");
                return ['status' => 'error', 'message' => 'Invalid phone number format. Must be 11 digits starting with 01.'];
            }

            if ($driver === 'bulksmsbd') {
                return $this->sendBulkSmsBd($apiKey, $senderId, $cleanedNumber, $message);
            } elseif ($driver === 'mimsms') {
                return $this->sendMimSms($apiKey, $senderId, $cleanedNumber, $message);
            }

            return ['status' => 'error', 'message' => "Unsupported SMS driver: {$driver}"];

        } catch (\Throwable $e) {
            Log::error("SMS Transmission crash: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send SMS via BulkSMSBD API.
     */
    private function sendBulkSmsBd(string $apiKey, string $senderId, string $number, string $message): array
    {
        $url = "http://bulksmsbd.net/api/smsapi";

        try {
            $response = Http::asForm()->post($url, [
                'api_key' => $apiKey,
                'senderid' => $senderId,
                'number' => $number,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $code = $json['response_code'] ?? null;
                
                // 202 means Submitted Successfully according to BulkSMSBD docs
                if ($code == 202) {
                    Log::info("BulkSMSBD success to {$number}: " . ($json['success_message'] ?? 'SMS Submitted Successfully'));
                    return ['status' => 'success', 'data' => $json];
                } else {
                    $errorMsg = $json['error_message'] ?? 'Submission failed';
                    Log::error("BulkSMSBD failed code {$code} to {$number}: " . $errorMsg);
                    return ['status' => 'failed', 'code' => $code, 'message' => $errorMsg];
                }
            }

            Log::error("BulkSMSBD HTTP failure to {$number}. Status: " . $response->status());
            return ['status' => 'failed', 'message' => 'HTTP request failed with status: ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error("BulkSMSBD request exception: " . $e->getMessage());
            return ['status' => 'failed', 'message' => 'API connection exception: ' . $e->getMessage()];
        }
    }

    /**
     * Send SMS via MimSMS API.
     */
    private function sendMimSms(string $apiKey, string $senderId, string $number, string $message): array
    {
        $url = "https://api.mimsms.com/sms/send";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'sender' => $senderId,
                'number' => $number,
                'message' => $message,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $success = $json['success'] ?? false;
                if ($success) {
                    Log::info("MimSMS success to {$number}: " . ($json['message'] ?? 'SMS Submitted Successfully'));
                    return ['status' => 'success', 'data' => $json];
                } else {
                    $errorMsg = $json['message'] ?? 'Submission failed';
                    Log::error("MimSMS failed to {$number}: " . $errorMsg);
                    return ['status' => 'failed', 'message' => $errorMsg];
                }
            }

            Log::error("MimSMS HTTP failure to {$number}. Status: " . $response->status());
            return ['status' => 'failed', 'message' => 'HTTP request failed with status: ' . $response->status()];
        } catch (\Throwable $e) {
            Log::error("MimSMS request exception: " . $e->getMessage());
            return ['status' => 'failed', 'message' => 'API connection exception: ' . $e->getMessage()];
        }
    }

    /**
     * Clean phone number format for BulkSMSBD (starts with 01, exactly 11 digits).
     */
    public function cleanPhoneNumber(string $phone): string
    {
        // Strip out all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // If it starts with 8801, strip 88 to get 11 digits starting with 01
        if (str_starts_with($cleaned, '8801')) {
            $cleaned = substr($cleaned, 2);
        }

        // If it starts with 1 (e.g. 17xxxxxxxx), prepend 0 to make it 01
        if (str_starts_with($cleaned, '1') && strlen($cleaned) === 10) {
            $cleaned = '0' . $cleaned;
        }

        return $cleaned;
    }
}
