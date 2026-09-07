<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance Promotions Table
        Schema::table('promotions', function (Blueprint $table) {
            // Change status to string for broader status support
            $table->string('status', 50)->default('pending_review')->change();

            // Ranking & override parameters
            $table->boolean('is_pinned')->default(false)->after('moderation_status');
            $table->string('ranking_override', 20)->default('none')->after('is_pinned'); // none, boost, demote
            $table->decimal('relevance_override', 5, 2)->default(1.00)->after('ranking_override');
            $table->decimal('bid_override', 5, 2)->default(1.00)->after('relevance_override');
            $table->decimal('ctr_override', 5, 2)->default(1.00)->after('bid_override');
            $table->decimal('freshness_override', 5, 2)->default(1.00)->after('ctr_override');

            // AI Safety & Moderation risk scores
            $table->decimal('spam_score', 5, 2)->default(0.00)->after('freshness_override');
            $table->decimal('fraud_score', 5, 2)->default(0.00)->after('spam_score');
            $table->decimal('link_safety_score', 5, 2)->default(1.00)->after('fraud_score'); // 1.00 = safe, 0.00 = bad
            $table->decimal('content_policy_score', 5, 2)->default(0.00)->after('link_safety_score');
            $table->decimal('duplicate_score', 5, 2)->default(0.00)->after('content_policy_score');
            $table->decimal('anomaly_score', 5, 2)->default(0.00)->after('duplicate_score');
            $table->text('ai_explanation')->nullable()->after('anomaly_score');
            $table->boolean('whitelisted_employer')->default(false)->after('ai_explanation');

            // Fraud Protection Statuses
            $table->boolean('is_suspended')->default(false)->after('whitelisted_employer');
            $table->string('fraud_reason')->nullable()->after('is_suspended');
            $table->json('suspicious_activity_logs')->nullable()->after('fraud_reason');
        });

        // 2. Enhance Wallets Table for freezing & billing overrides
        Schema::table('wallets', function (Blueprint $table) {
            $table->boolean('is_frozen')->default(false)->after('withdrawable_balance');
            $table->string('freeze_reason')->nullable()->after('is_frozen');
        });

        // 3. Enhance Promotion Ledgers for advanced tracking
        Schema::table('promotion_ledgers', function (Blueprint $table) {
            $table->string('status', 50)->default('completed')->after('amount_deducted'); // completed, failed, disputed, refunded
            $table->string('failure_reason')->nullable()->after('status');
            $table->json('revenue_split')->nullable()->after('failure_reason'); // e.g. platform split vs employer split
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_ledgers', function (Blueprint $table) {
            $table->dropColumn(['status', 'failure_reason', 'revenue_split']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['is_frozen', 'freeze_reason']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'is_pinned', 'ranking_override', 'relevance_override', 'bid_override', 
                'ctr_override', 'freshness_override', 'spam_score', 'fraud_score', 
                'link_safety_score', 'content_policy_score', 'duplicate_score', 
                'anomaly_score', 'ai_explanation', 'whitelisted_employer', 
                'is_suspended', 'fraud_reason', 'suspicious_activity_logs'
            ]);
        });
    }
};
