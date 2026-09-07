<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Add Targeting and Billing tracking to Promotions
        Schema::table('promotions', function (Blueprint $table) {
            $table->json('target_skills')->nullable()->after('type');
            $table->string('target_location')->nullable()->after('target_skills');
            $table->string('target_experience')->nullable()->after('target_location');
            $table->timestamp('last_billed_at')->nullable()->after('status');
        });

        // 2. Promotion Billing Ledger
        Schema::create('promotion_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_deducted', 10, 2);
            $table->timestamp('billed_for_hour');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('promotion_ledgers');
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['target_skills', 'target_location', 'target_experience', 'last_billed_at']);
        });
    }
};