<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Section 1: Expand user_profiles ───
        Schema::table('user_profiles', function (Blueprint $table) {
            // Section 1 – Personal
            $table->string('full_name_bn')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->default('Bangladeshi');
            $table->string('national_id')->nullable();
            $table->string('birth_reg_no')->nullable();

            // Section 2 – Contact
            $table->string('alt_phone')->nullable();
            $table->text('present_address')->nullable();
            $table->text('permanent_address')->nullable();
            $table->string('district')->nullable();
            $table->string('division')->nullable();
            $table->string('postal_code')->nullable();

            // Section 3 – Career
            $table->text('career_objective')->nullable();
            $table->string('current_profession')->nullable();
            $table->string('expected_job_category')->nullable();
            $table->string('preferred_location')->nullable();
            $table->string('expected_salary')->nullable();
            $table->boolean('available_remote')->default(false);
            $table->boolean('available_relocation')->default(false);

            // Section 6 – Skills
            $table->json('computer_skills')->nullable();
            $table->json('microsoft_office_level')->nullable();
            $table->json('other_skills')->nullable();

            // Section 7 – Language Skills
            $table->json('language_proficiency')->nullable();

            // Section 12 – Social Links
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('facebook_url')->nullable();

            // Section 13 – Job Preferences
            $table->string('preferred_industry')->nullable();
            $table->string('preferred_district')->nullable();

            // Section 15 – Premium
            $table->boolean('one_click_apply')->default(false);
            $table->boolean('job_alert_enabled')->default(false);
            $table->json('application_tracking')->nullable();
        });

        // ─── Section 4: Education ───
        Schema::create('candidate_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level'); // ssc, hsc, graduation, post_graduation
            $table->string('board')->nullable();
            $table->string('group_or_subject')->nullable();
            $table->string('degree_name')->nullable();
            $table->string('institute_name')->nullable();
            $table->unsignedSmallInteger('passing_year')->nullable();
            $table->decimal('gpa_or_cgpa', 4, 2)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        // ─── Section 5: Work Experience ───
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

        // ─── Section 8: Training ───
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

        // ─── Section 9: Certifications ───
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

        // ─── Section 10: References ───
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

        // ─── Section 11: Documents ───
        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // cv, nid, passport, academic_cert, experience_cert, photo
            $table->string('label')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
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
                'full_name_bn', 'full_name_en', 'father_name', 'mother_name',
                'date_of_birth', 'gender', 'marital_status', 'nationality',
                'national_id', 'birth_reg_no',
                'alt_phone', 'present_address', 'permanent_address',
                'district', 'division', 'postal_code',
                'career_objective', 'current_profession', 'expected_job_category',
                'preferred_location', 'expected_salary', 'available_remote', 'available_relocation',
                'computer_skills', 'microsoft_office_level', 'other_skills',
                'language_proficiency',
                'portfolio_url', 'linkedin_url', 'github_url', 'facebook_url',
                'preferred_industry', 'preferred_district',
                'one_click_apply', 'job_alert_enabled', 'application_tracking',
            ]);
        });
    }
};
