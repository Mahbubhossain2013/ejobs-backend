<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Employer
            $table->foreignId('job_id')->nullable()->constrained()->onDelete('cascade'); // Target Job
            
            $table->string('title'); // Campaign Name
            $table->string('type')->default('sponsored_job'); // sponsored_job, banner_ad, company_boost
            
            $table->decimal('daily_budget', 12, 2);
            $table->decimal('total_budget', 12, 2);
            $table->decimal('spent_amount', 12, 2)->default(0);
            
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            
            $table->enum('status', ['draft', 'pending_payment', 'active', 'paused', 'expired', 'cancelled'])->default('pending_payment');
            
            // Analytics Tracking (Updated via separate cron/workers later)
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('promotions'); }
};