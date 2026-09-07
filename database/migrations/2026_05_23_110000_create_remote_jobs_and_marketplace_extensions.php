<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend Jobs Table to support freelance filters & options
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'budget_type')) {
                $table->string('budget_type')->default('fixed')->after('budget'); // fixed, hourly
            }
            if (!Schema::hasColumn('jobs', 'required_skills')) {
                $table->json('required_skills')->nullable()->after('budget_type');
            }
            if (!Schema::hasColumn('jobs', 'project_duration')) {
                $table->string('project_duration')->nullable()->after('required_skills');
            }
            if (!Schema::hasColumn('jobs', 'experience_level')) {
                $table->string('experience_level')->nullable()->after('project_duration'); // entry, intermediate, expert
            }
            if (!Schema::hasColumn('jobs', 'timezone')) {
                $table->string('timezone')->nullable()->after('experience_level');
            }
            if (!Schema::hasColumn('jobs', 'country_restriction')) {
                $table->string('country_restriction')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('jobs', 'language_requirement')) {
                $table->string('language_requirement')->nullable()->after('country_restriction');
            }
            if (!Schema::hasColumn('jobs', 'visibility')) {
                $table->string('visibility')->default('public')->after('language_requirement'); // public, invite_only
            }
        });

        // 2. Extend Users Table to support currency preferences
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'currency_preference')) {
                $table->string('currency_preference')->default('BDT')->after('password'); // BDT, USD
            }
        });

        // 3. Create Disputes Table
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade');
            $table->foreignId('opened_by')->constrained('users')->onDelete('cascade');
            $table->text('reason');
            $table->string('proof_path')->nullable();
            $table->decimal('refund_percentage', 5, 2)->default(0.00); // Percentage returned to Employer
            $table->string('status')->default('open'); // open, resolved, refunded, split
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        // 4. Create Secure Workspace Credentials Safe Table
        Schema::create('secure_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('jobs')->onDelete('cascade'); // The workspace project
            $table->string('key_name'); // e.g., 'cPanel Login', 'Stripe API Key'
            $table->text('username')->nullable(); // encrypted string
            $table->text('password')->nullable(); // encrypted string
            $table->text('notes')->nullable();    // encrypted string
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        // 5. Create AI Moderation Logs Table
        Schema::create('ai_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message_text');
            $table->json('detected_violations'); // array of categories matched
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->string('provider')->nullable(); // e.g. Gemini, OpenAI
            $table->string('action_taken')->default('blocked'); // blocked, warned, suspended
            $table->timestamps();
        });

        // 6. Create User Trust Scores Table
        Schema::create('user_trust_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->unique();
            $table->integer('trust_score')->default(100);
            $table->string('risk_level')->default('low'); // low, medium, high
            $table->integer('violation_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_trust_scores');
        Schema::dropIfExists('ai_moderation_logs');
        Schema::dropIfExists('secure_credentials');
        Schema::dropIfExists('disputes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['currency_preference']);
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'budget_type',
                'required_skills',
                'project_duration',
                'experience_level',
                'timezone',
                'country_restriction',
                'language_requirement',
                'visibility'
            ]);
        });
    }
};
