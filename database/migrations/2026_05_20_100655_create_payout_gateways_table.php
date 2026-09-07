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
        Schema::create('payout_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., bKash, Bank Transfer
            $table->decimal('min_amount', 12, 2)->default(500);
            $table->decimal('max_amount', 12, 2)->default(25000);
            $table->decimal('fixed_charge', 12, 2)->default(0);
            // JSON to store fields like: [{"label": "Wallet Number", "type": "text", "name": "number"}]
            $table->json('user_input')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_gateways');
    }
};
