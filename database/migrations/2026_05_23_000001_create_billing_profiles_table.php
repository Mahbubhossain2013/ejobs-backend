<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('billing_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 3)->nullable()->index();
            $table->string('vat_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('currency_code', 10)->default('USD');
            $table->boolean('is_business')->default(false);
            $table->boolean('is_default')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('user_id'); // one billing profile per user for now
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_profiles');
    }
};
