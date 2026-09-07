<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend wallet_transactions with invoice linkage and balance snapshot
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id')->constrained('invoices')->nullOnDelete();
            $table->decimal('balance_after', 14, 2)->nullable()->after('amount'); // snapshot of balance after transaction
            $table->string('currency_code', 10)->nullable()->after('balance_after');
            $table->string('meta_notes')->nullable()->after('description'); // extra notes
        });

        // Extend escrows table with invoice linkage
        if (Schema::hasTable('escrows')) {
            Schema::table('escrows', function (Blueprint $table) {
                $table->foreignId('funding_invoice_id')->nullable()->after('id')->constrained('invoices')->nullOnDelete();
                $table->foreignId('release_invoice_id')->nullable()->after('funding_invoice_id')->constrained('invoices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'balance_after', 'currency_code', 'meta_notes']);
        });

        if (Schema::hasTable('escrows')) {
            Schema::table('escrows', function (Blueprint $table) {
                $table->dropForeign(['funding_invoice_id']);
                $table->dropForeign(['release_invoice_id']);
                $table->dropColumn(['funding_invoice_id', 'release_invoice_id']);
            });
        }
    }
};
