<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        // Seed dynamic sample FAQ matching user request
        DB::table('faqs')->insert([
            [
                'title' => 'Bank Transfer Payment Method',
                'content' => 'To complete a payment via manual Bank Transfer, choose the Bank Transfer option at checkout, transfer the exact invoice amount to our routing account, and submit your transaction slip for admin review.',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
