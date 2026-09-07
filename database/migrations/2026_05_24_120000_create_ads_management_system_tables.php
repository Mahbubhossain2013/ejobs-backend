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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('media_type')->default('image'); // image, video, html, script, url
            $table->text('media_path')->nullable(); // Upload path or script code content
            $table->string('target_url')->nullable();
            $table->string('cta_text')->default('Learn More');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('draft'); // draft, active, paused
            $table->integer('priority')->default(0);
            $table->string('approval_status')->default('approved'); // pending, approved, rejected
            $table->string('monetization_model')->default('fixed'); // cpc, cpm, fixed
            $table->decimal('rate', 10, 2)->default(0.00);
            $table->integer('max_clicks')->nullable();
            $table->integer('max_impressions')->nullable();
            $table->integer('total_clicks')->default(0);
            $table->integer('total_impressions')->default(0);
            $table->string('role_target')->default('all'); // all, candidate, employer, guest
            $table->string('language_target')->default('all'); // all, en, bn
            $table->string('device_target')->default('all'); // all, mobile, desktop
            $table->string('subscription_target')->nullable();
            $table->string('location_target')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->string('slot'); // homepage_hero, sidebar_candidate, sidebar_employer, inline_jobs, feed, widget, popup, sticky_banner
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->string('event_type'); // impression, click
            $table->string('ip_address')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_agent')->nullable();
            $table->string('device')->default('desktop'); // mobile, desktop
            $table->decimal('revenue', 10, 4)->default(0.0000);
            $table->timestamps();
        });

        Schema::create('ad_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // create, edit, status_change
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_audits');
        Schema::dropIfExists('ad_logs');
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('ads');
    }
};
