<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AI Algorithm Settings table
        Schema::create('ai_algorithm_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('skills_matching_weight')->default(40);
            $table->integer('saved_jobs_weight')->default(10);
            $table->integer('company_follows_weight')->default(10);
            $table->integer('recent_activity_weight')->default(10);
            $table->integer('location_relevance_weight')->default(10);
            $table->integer('premium_boosting_weight')->default(20);
            
            $table->boolean('track_job_views')->default(true);
            $table->boolean('track_job_saves')->default(true);
            $table->boolean('track_applications')->default(true);
            $table->boolean('track_profile_visits')->default(true);
            $table->boolean('track_company_visits')->default(true);
            $table->boolean('track_search_history')->default(true);
            $table->boolean('track_scroll_depth')->default(true);
            $table->boolean('track_click_patterns')->default(true);
            $table->boolean('track_session_engagement')->default(true);
            $table->timestamps();
        });

        // Seed initial algorithm settings row
        DB::table('ai_algorithm_settings')->insert([
            'skills_matching_weight' => 40,
            'saved_jobs_weight' => 10,
            'company_follows_weight' => 10,
            'recent_activity_weight' => 10,
            'location_relevance_weight' => 10,
            'premium_boosting_weight' => 20,
            'track_job_views' => true,
            'track_job_saves' => true,
            'track_applications' => true,
            'track_profile_visits' => true,
            'track_company_visits' => true,
            'track_search_history' => true,
            'track_scroll_depth' => true,
            'track_click_patterns' => true,
            'track_session_engagement' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. AI Configs modifications
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->integer('priority')->default(1);
            $table->integer('cost_rating')->default(3);
            $table->integer('speed_rating')->default(3);
            $table->integer('accuracy_rating')->default(3);
            $table->boolean('is_active')->default(true);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_failed_at')->nullable();
        });

        // Set Gemini to priority 1, OpenAI to priority 2, OpenRouter to priority 3
        DB::table('ai_configs')->where('provider_key', 'gemini')->update(['priority' => 1]);
        DB::table('ai_configs')->where('provider_key', 'openai')->update(['priority' => 2]);
        DB::table('ai_configs')->where('provider_key', 'openrouter')->update(['priority' => 3]);

        // 3. User Behavior Logs table
        Schema::create('user_behavior_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('activity_type')->index(); // 'job_view', 'job_save', etc.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. User Profiles risk/quality override fields
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->integer('ai_risk_score')->default(0);
            $table->integer('ai_quality_score')->default(100);
            $table->integer('manual_override_score')->nullable();
            $table->string('manual_override_status')->nullable();
            $table->text('override_reason')->nullable();
            $table->text('behavior_summary')->nullable();
        });

        // 5. Companies risk/quality override fields
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('ai_risk_score')->default(0);
            $table->integer('ai_quality_score')->default(100);
            $table->integer('manual_override_score')->nullable();
            $table->string('manual_override_status')->nullable();
            $table->text('override_reason')->nullable();
            $table->text('behavior_summary')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_algorithm_settings');
        Schema::dropIfExists('user_behavior_logs');

        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn(['priority', 'cost_rating', 'speed_rating', 'accuracy_rating', 'is_active', 'failure_count', 'last_failed_at']);
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['ai_risk_score', 'ai_quality_score', 'manual_override_score', 'manual_override_status', 'override_reason', 'behavior_summary']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['ai_risk_score', 'ai_quality_score', 'manual_override_score', 'manual_override_status', 'override_reason', 'behavior_summary']);
        });
    }
};
