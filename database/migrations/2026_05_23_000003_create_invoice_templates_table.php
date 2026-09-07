<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('template_view')->default('invoices.templates.default'); // Blade view path
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('watermark_text')->nullable();
            $table->string('watermark_path')->nullable();

            // Colors
            $table->string('primary_color')->default('#1a56db');
            $table->string('secondary_color')->default('#e1effe');
            $table->string('accent_color')->default('#1c64f2');
            $table->string('text_color')->default('#111827');

            // Company info (shown in invoice header)
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_website')->nullable();
            $table->string('company_vat_label')->default('VAT No.');
            $table->string('company_vat_number')->nullable();

            // Invoice content
            $table->text('footer_text')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->string('tax_label')->default('VAT');
            $table->string('currency_symbol')->default('$');

            // Advanced
            $table->text('custom_css')->nullable();
            $table->json('settings')->nullable(); // extra JSON for future flexibility

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
    }
};
