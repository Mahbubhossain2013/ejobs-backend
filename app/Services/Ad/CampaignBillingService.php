<?php

namespace App\Services\Ad;

use App\Models\Promotion;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CampaignBillingService
{
    /**
     * Lock 1-day daily budget upfront from Available Balance to Locked Balance
     */
    public static function lockUpfrontBudget(Promotion $promo): bool
    {
        return DB::transaction(function () use ($promo) {
            $wallet = Wallet::where('user_id', $promo->user_id)->lockForUpdate()->first();

            if (!$wallet) {
                // Create wallet if missing
                $wallet = Wallet::create([
                    'user_id' => $promo->user_id,
                    'balance' => 0.00,
                    'locked_balance' => 0.00,
                    'withdrawable_balance' => 0.00
                ]);
            }

            $dailyBudget = $promo->daily_budget;

            // Insufficient check
            if ($wallet->balance < $dailyBudget) {
                Log::warning("Promotion Billing: Upfront lock failed for Promotion {$promo->id} due to low available balance.");
                return false;
            }

            // Lock daily budget
            $wallet->balance -= $dailyBudget;
            $wallet->locked_balance += $dailyBudget;
            $wallet->save();

            // Create prepaid lock transaction
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $dailyBudget,
                'reference_type' => 'promotion_lock',
                'reference_id' => $promo->id,
                'description' => "Upfront 1-day budget lock for campaign: {$promo->title}",
                'status' => 'completed'
            ]);

            return true;
        });
    }

    /**
     * Release residual locked balance back to available balance when campaign is paused/ended
     */
    public static function releaseLockedBudget(Promotion $promo): void
    {
        DB::transaction(function () use ($promo) {
            $wallet = Wallet::where('user_id', $promo->user_id)->lockForUpdate()->first();
            if (!$wallet) return;

            // Calculate remaining lock (how much of today's locked daily budget is unused)
            // A conservative estimation: locked_balance is released up to the daily budget
            $unusedLocked = min($wallet->locked_balance, $promo->daily_budget);

            if ($unusedLocked > 0) {
                $wallet->locked_balance -= $unusedLocked;
                $wallet->balance += $unusedLocked;
                $wallet->save();

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'credit',
                    'amount' => $unusedLocked,
                    'reference_type' => 'promotion_unlock',
                    'reference_id' => $promo->id,
                    'description' => "Unused locked budget released for paused/ended campaign: {$promo->title}",
                    'status' => 'completed'
                ]);
            }
        });
    }

    /**
     * Execute billing routines across all active promotions
     */
    public static function processActivePromotionsBilling(): void
    {
        $promotions = Promotion::where('status', 'active')->get();

        foreach ($promotions as $promo) {
            // Check if end date reached
            if ($promo->end_date && now()->greaterThan($promo->end_date)) {
                self::releaseLockedBudget($promo);
                $promo->update(['status' => 'expired']);
                continue;
            }

            // Calculate exact hourly rate
            $hourlyRate = round($promo->daily_budget / 24, 2);

            try {
                DB::transaction(function () use ($promo, $hourlyRate) {
                    $wallet = Wallet::where('user_id', $promo->user_id)->lockForUpdate()->first();

                    // If total budget is exceeded, complete campaign
                    if ($promo->spent_amount + $hourlyRate >= $promo->total_budget) {
                        self::releaseLockedBudget($promo);
                        $promo->update(['status' => 'expired']);
                        return;
                    }

                    // Safety check: Auto-Pause if wallet is frozen
                    if ($wallet && $wallet->is_frozen) {
                        self::releaseLockedBudget($promo);
                        $promo->update([
                            'status' => 'paused',
                            'rejection_reason' => 'Auto-paused: Employer wallet is frozen.'
                        ]);
                        return;
                    }

                    // Auto-Pause if locked balance runs out (meaning daily lock has completed)
                    // If locked balance is empty, attempt to renew 1-day budget lock
                    if (!$wallet || $wallet->locked_balance < $hourlyRate) {
                        if ($wallet && $wallet->balance >= $promo->daily_budget) {
                            // Renew lock for another 24 hours
                            $wallet->balance -= $promo->daily_budget;
                            $wallet->locked_balance += $promo->daily_budget;
                            $wallet->save();

                            WalletTransaction::create([
                                'wallet_id' => $wallet->id,
                                'type' => 'debit',
                                'amount' => $promo->daily_budget,
                                'reference_type' => 'promotion_lock',
                                'reference_id' => $promo->id,
                                'description' => "Renewed upfront 1-day budget lock for campaign: {$promo->title}",
                                'status' => 'completed'
                            ]);
                        } else {
                            // Insufficient available balance for renewal: auto-pause campaign instantly
                            $promo->update(['status' => 'paused', 'rejection_reason' => 'Auto-paused: Insufficient wallet balance for daily budget lock renewal.']);
                            return;
                        }
                    }

                    // Deduct from Locked Balance hourly
                    $wallet->locked_balance -= $hourlyRate;
                    $wallet->save();

                    // Log spent wallet transaction
                    $wallet->transactions()->create([
                        'type' => 'debit',
                        'amount' => $hourlyRate,
                        'reference_type' => 'promotion_billing',
                        'reference_id' => $promo->id,
                        'description' => "Hourly ad spend deduction for campaign: {$promo->title}",
                        'status' => 'completed'
                    ]);

                    // Add spent log in ledger
                    DB::table('promotion_ledgers')->insert([
                        'promotion_id' => $promo->id,
                        'user_id' => $promo->user_id,
                        'amount_deducted' => $hourlyRate,
                        'billed_for_hour' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Update spent aggregates
                    $promo->update([
                        'spent_amount' => $promo->spent_amount + $hourlyRate,
                        'last_billed_at' => now()
                    ]);
                });
            } catch (\Exception $e) {
                Log::error("Campaign Billing Service Error on Promo ID {$promo->id}: " . $e->getMessage());
            }
        }
    }
}
