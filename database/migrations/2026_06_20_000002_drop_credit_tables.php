<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('user_credits');
        Schema::dropIfExists('credit_configurations');
    }

    public function down(): void
    {
        // Cannot easily recreate — credit system is being removed
    }
};
