<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event')->index(); // invoice.created, invoice.paid, invoice.sent, invoice.voided, invoice.refunded, pdf.generated, etc.
            $table->string('actor_type')->default('system'); // system, admin, user
            $table->unsignedBigInteger('actor_id')->nullable(); // admin user id if done by admin

            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->json('old_values')->nullable();  // for change tracking
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            // No updated_at — logs are immutable
            $table->timestamp('created_at')->useCurrent();

            $table->index(['invoice_id', 'event']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
