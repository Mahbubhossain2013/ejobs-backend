<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Fix existing withdrawable_balance values that were not decremented
        // when CV templates were purchased. Subtract all completed cv_template_purchase
        // debit transactions from withdrawable_balance.
        $wallets = DB::table('wallets')->get();

        foreach ($wallets as $wallet) {
            $totalTemplatePurchases = DB::table('wallet_transactions')
                ->where('wallet_id', $wallet->id)
                ->where('type', 'debit')
                ->where('reference_type', 'cv_template_purchase')
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalTemplatePurchases > 0) {
                $newWithdrawable = max(0, $wallet->withdrawable_balance - $totalTemplatePurchases);
                DB::table('wallets')
                    ->where('id', $wallet->id)
                    ->update(['withdrawable_balance' => $newWithdrawable]);

                Log::info("Fixed withdrawable_balance for wallet {$wallet->id}: deducted {$totalTemplatePurchases} for CV template purchases (was {$wallet->withdrawable_balance}, now {$newWithdrawable})");
            }
        }
    }

    public function down(): void
    {
        // This migration corrects data; reversing it would re-inflate balances.
        // No down migration needed.
    }
};
