<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');                           // e.g. "VAT", "GST", "Sales Tax"
            $table->string('label')->default('VAT');          // Display label on invoice
            $table->decimal('rate', 8, 4)->default(0);        // e.g. 15.0000 for 15%
            $table->string('country_code', 3)->nullable();    // null = global fallback
            $table->string('region')->nullable();             // state/province if needed
            $table->boolean('is_inclusive')->default(false);  // tax included in price?
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('applies_to')->default('all');     // all | subscription | boost | milestone
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
