<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('campaign_type')->default('sponsored_job')->after('type');
            $table->json('target_categories')->nullable()->after('target_skills');
            $table->string('target_devices')->default('all')->after('target_location');
            $table->json('target_behaviors')->nullable()->after('target_experience');
            $table->decimal('relevance_score', 5, 2)->default(1.00)->after('clicks');
            $table->decimal('ctr_score', 5, 2)->default(0.00)->after('relevance_score');
            $table->string('rejection_reason')->nullable()->after('status');
            $table->string('moderation_status')->default('safe')->after('rejection_reason'); // safe, uncertain, high_risk
        });
    }

    public function down(): void {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_type', 'target_categories', 'target_devices', 
                'target_behaviors', 'relevance_score', 'ctr_score', 
                'rejection_reason', 'moderation_status'
            ]);
        });
    }
};
