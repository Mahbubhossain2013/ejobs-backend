<?php

namespace App\Services\Ad;

use App\Models\Promotion;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdFraudDetectionService
{
    /**
     * Track a click and detect potential fraud patterns.
     * Returns true if fraudulent behavior is detected and mitigated.
     */
    public static function detectClickFraud(Promotion $promo, string $ipAddress, ?string $userAgent = null): bool
    {
        // 1. Click Spike Detection
        // In a real-world scenario, we check AdLog or clicks within the last hour.
        // Let's query recent click logs for this promotion from the same IP.
        $recentClicks = DB::table('ad_logs')
            ->where('ad_id', $promo->id)
            ->where('event_type', 'click')
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        // Mitigation: If more than 5 clicks in 15 minutes from same IP, trigger click fraud
        if ($recentClicks > 5) {
            self::flagCampaignFraud($promo, "Excessive click pattern detected from IP {$ipAddress}: {$recentClicks} clicks in 15 mins.");
            return true;
        }

        // 2. VPN/Proxy or Bot User Agent checks
        if ($userAgent) {
            $userAgentLower = strtolower($userAgent);
            $botKeywords = ['bot', 'crawler', 'spider', 'headlesschrome', 'selenium', 'puppeteer', 'python-requests'];
            
            foreach ($botKeywords as $keyword) {
                if (str_contains($userAgentLower, $keyword)) {
                    self::flagCampaignFraud($promo, "Bot traffic signature detected: User-Agent '{$userAgent}' matches '{$keyword}'.");
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Flag a campaign for fraud, auto suspend it, and temporarily freeze the employer's wallet.
     */
    public static function flagCampaignFraud(Promotion $promo, string $reason): void
    {
        DB::transaction(function () use ($promo, $reason) {
            // Update Promotion status to suspended
            $promo->update([
                'status' => 'suspended',
                'is_suspended' => true,
                'fraud_reason' => $reason,
                'suspicious_activity_logs' => array_merge(
                    $promo->suspicious_activity_logs ?? [],
                    [[
                        'timestamp' => now()->toDateTimeString(),
                        'trigger' => $reason,
                        'action' => 'Auto-Suspended Campaign & Locked Wallet'
                    ]]
                )
            ]);

            // Release any locked budgets
            CampaignBillingService::releaseLockedBudget($promo);

            // Freeze the employer wallet
            $wallet = Wallet::where('user_id', $promo->user_id)->first();
            if ($wallet && !$wallet->is_frozen) {
                $wallet->update([
                    'is_frozen' => true,
                    'freeze_reason' => "Ad Fraud Auto-Lock: Suspicious activity on campaign {$promo->title}."
                ]);

                // Create a wallet log
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'debit',
                    'amount' => 0.00,
                    'reference_type' => 'wallet_freeze',
                    'reference_id' => $promo->id,
                    'description' => "Wallet frozen due to advertising fraud detection on Campaign ID {$promo->id}.",
                    'status' => 'completed'
                ]);
            }

            Log::warning("Ad Fraud Alert: Campaign ID {$promo->id} has been suspended. Employer User ID {$promo->user_id} wallet frozen.");
        });
    }
}
