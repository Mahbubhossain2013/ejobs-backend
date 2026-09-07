<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('job_title_custom')->nullable()->after('title');
            $table->foreignId('category_id')->nullable()->after('job_title_custom');
            $table->boolean('is_remote_project')->default(true);
            $table->json('deliverables')->nullable();
            $table->string('budget_type')->default('fixed');
            $table->enum('offer_status', [
                'draft',
                'pending_candidate',
                'candidate_accepted',
                'candidate_rejected',
                'employer_signed',
                'candidate_signed',
                'active',
                'completed',
                'terminated',
                'disputed',
            ])->default('draft')->after('status');
            $table->timestamp('offer_sent_at')->nullable();
            $table->timestamp('offer_expires_at')->nullable();
            $table->json('revision_history')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'job_title_custom', 'category_id', 'is_remote_project',
                'deliverables', 'budget_type', 'offer_status',
                'offer_sent_at', 'offer_expires_at', 'revision_history',
            ]);
        });
    }
};
