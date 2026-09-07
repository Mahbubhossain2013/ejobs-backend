<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Wallets table - add withdrawable_balance
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'withdrawable_balance')) {
                $table->decimal('withdrawable_balance', 12, 2)->default(0)->after('locked_balance');
            }
        });

        // Copy current balance to withdrawable_balance for existing wallets
        try {
            DB::table('wallets')->update(['withdrawable_balance' => DB::raw('balance')]);
        } catch (\Throwable $e) {
            // Log or ignore if table is empty or error occurs
        }

        // 2. Withdrawals table - add payout_gateway_id, charge, payable
        Schema::table('withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawals', 'payout_gateway_id')) {
                $table->foreignId('payout_gateway_id')
                      ->nullable()
                      ->after('user_id')
                      ->constrained('payout_gateways')
                      ->nullOnDelete();
            }
            if (!Schema::hasColumn('withdrawals', 'charge')) {
                $table->decimal('charge', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('withdrawals', 'payable')) {
                $table->decimal('payable', 12, 2)->default(0)->after('charge');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'withdrawable_balance')) {
                $table->dropColumn('withdrawable_balance');
            }
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'payout_gateway_id')) {
                $table->dropForeign(['payout_gateway_id']);
                $table->dropColumn('payout_gateway_id');
            }
            if (Schema::hasColumn('withdrawals', 'charge')) {
                $table->dropColumn('charge');
            }
            if (Schema::hasColumn('withdrawals', 'payable')) {
                $table->dropColumn('payable');
            }
        });
    }
};
