<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['payment', 'refund', 'adjustment', 'credit', 'chargeback'])->default('payment');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled', 'disputed'])->default('pending');

            $table->decimal('amount', 14, 2);
            $table->string('currency_code', 10)->default('USD');
            $table->string('payment_method')->nullable();     // wallet, stripe, paypal, bank_transfer
            $table->string('payment_gateway')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('gateway_reference')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('gateway_response')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_transactions');
    }
};
