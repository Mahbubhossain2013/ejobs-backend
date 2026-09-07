<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add remaining columns that weren't created in the partial migration
        Schema::table('user_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('user_profiles', 'language_proficiency')) $table->json('language_proficiency')->nullable();
            if (!Schema::hasColumn('user_profiles', 'portfolio_url')) $table->string('portfolio_url')->nullable();
            if (!Schema::hasColumn('user_profiles', 'linkedin_url')) $table->string('linkedin_url')->nullable();
            if (!Schema::hasColumn('user_profiles', 'github_url')) $table->string('github_url')->nullable();
            if (!Schema::hasColumn('user_profiles', 'facebook_url')) $table->string('facebook_url')->nullable();
            if (!Schema::hasColumn('user_profiles', 'preferred_industry')) $table->string('preferred_industry')->nullable();
            if (!Schema::hasColumn('user_profiles', 'preferred_district')) $table->string('preferred_district')->nullable();
            if (!Schema::hasColumn('user_profiles', 'one_click_apply')) $table->boolean('one_click_apply')->default(false);
            if (!Schema::hasColumn('user_profiles', 'job_alert_enabled')) $table->boolean('job_alert_enabled')->default(false);
            if (!Schema::hasColumn('user_profiles', 'application_tracking')) $table->json('application_tracking')->nullable();
        });

        // Create tables only if they don't exist
        if (!Schema::hasTable('candidate_educations')) {
            Schema::create('candidate_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('level');
                $table->string('board')->nullable();
                $table->string('group_or_subject')->nullable();
                $table->string('degree_name')->nullable();
                $table->string('institute_name')->nullable();
                $table->unsignedSmallInteger('passing_year')->nullable();
                $table->decimal('gpa_or_cgpa', 4, 2)->nullable();
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('candidate_experiences')) {
            Schema::create('candidate_experiences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('company_name');
                $table->string('designation');
                $table->string('employment_type')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->text('responsibilities')->nullable();
                $table->string('salary')->nullable();
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('candidate_trainings')) {
            Schema::create('candidate_trainings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->string('institute_name')->nullable();
                $table->string('duration')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->string('certificate_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('candidate_certifications')) {
            Schema::create('candidate_certifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('organization')->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('certificate_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('candidate_references')) {
            Schema::create('candidate_references', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('designation')->nullable();
                $table->string('organization')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('candidate_documents')) {
            Schema::create('candidate_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type');
                $table->string('label')->nullable();
                $table->string('file_path');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidate_references');
        Schema::dropIfExists('candidate_certifications');
        Schema::dropIfExists('candidate_trainings');
        Schema::dropIfExists('candidate_experiences');
        Schema::dropIfExists('candidate_educations');

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'language_proficiency', 'portfolio_url', 'linkedin_url', 'github_url', 'facebook_url',
                'preferred_industry', 'preferred_district',
                'one_click_apply', 'job_alert_enabled', 'application_tracking',
            ]);
        });
    }
};
