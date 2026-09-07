<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            // Section 1: Basic Job Information
            $table->unsignedInteger('vacancies')->default(1)->after('title');
            $table->string('workplace_type')->nullable()->after('location'); // on-site, remote, hybrid
            $table->string('salary_type')->nullable()->after('salary_range'); // monthly, hourly, negotiable

            // Section 2: Company Information (stored on job for snapshot)
            $table->string('contact_person_name')->nullable()->after('company_id');
            $table->string('contact_email')->nullable()->after('contact_person_name');
            $table->string('contact_phone')->nullable()->after('contact_email');

            // Section 3: Job Description (granular)
            $table->text('job_summary')->nullable()->after('description');
            $table->text('responsibilities')->nullable()->after('job_summary');
            $table->text('education_requirements')->nullable()->after('responsibilities');
            $table->text('experience_requirements')->nullable()->after('education_requirements');
            $table->text('additional_requirements')->nullable()->after('experience_requirements');
            $table->text('benefits')->nullable()->after('additional_requirements');

            // Section 4: Candidate Requirements
            $table->unsignedTinyInteger('min_age')->nullable()->after('experience_level');
            $table->unsignedTinyInteger('max_age')->nullable()->after('min_age');
            $table->string('gender_preference')->nullable()->after('max_age'); // any, male, female, other
            $table->json('language_skills')->nullable()->after('gender_preference');
            $table->json('required_certifications')->nullable()->after('language_skills');
            $table->boolean('driving_license_required')->default(false)->after('required_certifications');

            // Section 5: Application Information
            $table->string('application_method')->default('internal')->after('deadline'); // internal, external, email
            $table->string('application_url')->nullable()->after('application_method');
            $table->string('application_email')->nullable()->after('application_url');
            $table->json('required_documents')->nullable()->after('application_email'); // cv, cover_letter, photo, certificate
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'vacancies', 'workplace_type', 'salary_type',
                'contact_person_name', 'contact_email', 'contact_phone',
                'job_summary', 'responsibilities', 'education_requirements',
                'experience_requirements', 'additional_requirements', 'benefits',
                'min_age', 'max_age', 'gender_preference', 'language_skills',
                'required_certifications', 'driving_license_required',
                'application_method', 'application_url', 'application_email', 'required_documents',
            ]);
        });
    }
};
