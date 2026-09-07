<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();

            // Type of invoice
            $table->enum('type', [
                'subscription',
                'job_boost',
                'featured_profile',
                'wallet_deposit',
                'wallet_withdrawal',
                'milestone',
                'contract_payment',
                'service_fee',
                'escrow_funding',
                'escrow_release',
                'refund',
                'manual',
            ])->index();

            // User relations
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('candidate_id')->nullable()->index();
            $table->unsignedBigInteger('employer_id')->nullable()->index();

            // Polymorphic reference (links to subscription, deposit, promotion, escrow, etc.)
            $table->string('reference_type')->nullable()->index();
            $table->unsignedBigInteger('reference_id')->nullable()->index();

            // Parent invoice (for refund invoices)
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            // Template
            $table->foreignId('invoice_template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();

            // Status
            $table->enum('status', [
                'draft',
                'pending',
                'paid',
                'overdue',
                'partially_paid',
                'refunded',
                'cancelled',
                'void',
            ])->default('pending')->index();

            // Amounts
            $table->string('currency_code', 10)->default('USD');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_rate', 8, 4)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('platform_fee_rate', 8, 4)->default(0);
            $table->decimal('platform_fee', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('amount_due', 14, 2)->default(0);

            // Billing info snapshot (denormalized for audit integrity)
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_company')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_vat_number')->nullable();
            $table->string('billing_country')->nullable();

            // Payment
            $table->string('payment_method')->nullable();       // wallet, stripe, paypal, etc.
            $table->string('payment_gateway')->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('issued_at')->nullable();

            // Delivery
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();

            // Content
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['created_at']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
