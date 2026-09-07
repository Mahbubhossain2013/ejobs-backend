<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('symbol');
            $table->decimal('rate', 16, 8)->default(1.00000000);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // Seed default currencies — BDT is the base currency (rate = 1.00000000)
        // Other rates are relative to 1 BDT (e.g. 1 BDT = 0.00851064 USD)
        DB::table('currencies')->insert([
            [
                'code' => 'BDT',
                'symbol' => '৳',
                'rate' => 1.00000000,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'symbol' => '$',
                'rate' => 0.00851064,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'EUR',
                'symbol' => '€',
                'rate' => 0.00780000,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GBP',
                'symbol' => '£',
                'rate' => 0.00670000,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'INR',
                'symbol' => '₹',
                'rate' => 0.71430000,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'SAR',
                'symbol' => '﷼',
                'rate' => 0.03190000,
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
