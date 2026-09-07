<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();

            // Bypass strict foreign keys to prevent Error 150
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('gateway_id');

            $table->decimal('amount', 12, 2);
            $table->decimal('charge', 12, 2)->default(0);
            $table->decimal('payable', 12, 2);

            $table->string('transaction_id')->unique();
            $table->string('proof_document')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_feedback')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
