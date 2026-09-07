<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Search Analytics Table
        Schema::create('search_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('query_string')->index();
            $table->json('filters')->nullable();
            $table->integer('result_count')->default(0);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Saved Searches Table
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('query_string');
            $table->json('filters')->nullable();
            $table->timestamps();
        });

        // 3. User Security Logs Table
        Schema::create('user_security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable()->index();
            $table->string('activity_type')->index(); // e.g. login, suspicious_activity, brute_force
            $table->integer('risk_score')->default(0);
            $table->boolean('vpn_detected')->default(false);
            $table->text('moderation_details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. SEO Settings Table
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique(); // e.g., 'home', 'jobs', 'remote-jobs', 'companies'
            $table->json('meta_title'); // support multilingual JSON
            $table->json('meta_description'); // support multilingual JSON
            $table->string('og_image_path')->nullable();
            $table->json('structured_data')->nullable(); // JSON-LD custom fields
            $table->timestamps();
        });

        // 5. Expand user_profiles with security & discoverability audits
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
                $table->json('login_device_history')->nullable();
                $table->json('active_sessions')->nullable();
                $table->string('restriction_status')->default('active')->index(); // active, shadow_restricted, suspended
                $table->boolean('ban_status')->default(false)->index();
                $table->text('moderation_notes')->nullable();
            }
        });

        // 6. Expand companies with security audits
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'last_login_ip')) {
                $table->string('last_login_ip')->nullable();
                $table->json('login_device_history')->nullable();
                $table->json('active_sessions')->nullable();
                $table->string('restriction_status')->default('active')->index();
                $table->boolean('ban_status')->default(false)->index();
                $table->text('moderation_notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_analytics');
        Schema::dropIfExists('saved_searches');
        Schema::dropIfExists('user_security_logs');
        Schema::dropIfExists('seo_settings');

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['last_login_ip', 'login_device_history', 'active_sessions', 'restriction_status', 'ban_status', 'moderation_notes']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['last_login_ip', 'login_device_history', 'active_sessions', 'restriction_status', 'ban_status', 'moderation_notes']);
        });
    }
};
