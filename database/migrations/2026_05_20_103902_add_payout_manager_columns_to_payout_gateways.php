<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payout_gateways', function (Blueprint $table) {
            // Add percent_charge if it doesn't exist
            if (!Schema::hasColumn('payout_gateways', 'percent_charge')) {
                $table->decimal('percent_charge', 5, 2)->default(0)->after('min_amount');
            }

            // Ensure max_amount and fixed_charge exist for safety
            if (!Schema::hasColumn('payout_gateways', 'max_amount')) {
                $table->decimal('max_amount', 12, 2)->default(50000)->after('percent_charge');
            }

            if (!Schema::hasColumn('payout_gateways', 'fixed_charge')) {
                $table->decimal('fixed_charge', 12, 2)->default(0)->after('max_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payout_gateways', function (Blueprint $table) {
            //
        });
    }
};
