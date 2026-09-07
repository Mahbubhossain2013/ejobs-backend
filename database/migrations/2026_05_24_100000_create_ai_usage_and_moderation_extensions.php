<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. AI Usage & Token Telemetry Log Table
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('provider')->index(); // gemini, openai, claude, deepseek
            $table->string('model_code')->index();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->integer('duration_ms')->default(0); // latency
            $table->string('status')->index(); // success, failure
            $table->text('error_message')->nullable();
            $table->boolean('was_fallback')->default(false);
            $table->boolean('is_cached')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Expand existing AI moderation logs table with resource tags if missing
        Schema::table('ai_moderation_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_moderation_logs', 'channel')) {
                $table->string('channel')->default('messages')->index(); // messages, workspace_chat, comments, reviews, posts
            }
            if (!Schema::hasColumn('ai_moderation_logs', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->index(); // ID of target message/review
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        
        Schema::table('ai_moderation_logs', function (Blueprint $table) {
            $table->dropColumn(['channel', 'target_id']);
        });
    }
};
