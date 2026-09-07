<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Expand companies table ──
        Schema::table('companies', function (Blueprint $table) {
            // Basic Info
            $table->string('name_bn')->nullable()->after('name');
            $table->string('tagline')->nullable()->after('name_bn');
            $table->string('company_type')->nullable()->after('industry'); // private, government, ngo, startup
            $table->string('business_registration_number')->nullable()->after('trade_license_number');
            $table->unsignedInteger('employee_count')->nullable()->after('size');

            // Contact Info
            $table->string('contact_person_name')->nullable()->after('description');
            $table->string('contact_person_designation')->nullable()->after('contact_person_name');
            $table->string('contact_phone')->nullable()->after('contact_person_designation');
            $table->string('contact_alt_phone')->nullable()->after('contact_phone');
            $table->string('contact_email')->nullable()->after('contact_alt_phone');

            // Address
            $table->text('head_office_address')->nullable()->after('location');
            $table->string('address_country')->nullable()->after('head_office_address');
            $table->string('address_division')->nullable()->after('address_country');
            $table->string('address_district')->nullable()->after('address_division');
            $table->string('address_postal_code')->nullable()->after('address_district');
            $table->string('google_map_embed')->nullable()->after('address_postal_code');

            // Company Overview
            $table->text('services_products')->nullable()->after('values');
            $table->text('working_culture')->nullable()->after('services_products');

            // HR Info
            $table->string('hr_manager_name')->nullable()->after('working_culture');
            $table->string('hr_contact_number')->nullable()->after('hr_manager_name');
            $table->string('hr_email')->nullable()->after('hr_contact_number');
            $table->text('recruitment_policy')->nullable()->after('hr_email');
            $table->text('hiring_process')->nullable()->after('recruitment_policy');

            // Job Posting Settings
            $table->boolean('allow_job_posting')->default(true)->after('hiring_process');
            $table->integer('job_posting_limit_monthly')->nullable()->after('allow_job_posting');
            $table->boolean('featured_job_allowed')->default(false)->after('job_posting_limit_monthly');
            $table->boolean('auto_approval')->default(false)->after('featured_job_allowed');
            $table->integer('job_expiry_days')->default(30)->after('auto_approval');

            // Verification Documents
            $table->string('nid_document')->nullable()->after('trade_license_document');
            $table->string('registration_certificate')->nullable()->after('nid_document');
            $table->string('tin_number')->nullable()->after('registration_certificate');
            $table->string('verification_status')->default('pending')->after('tin_number'); // pending, verified, rejected

            // Branding & Media
            $table->string('cover_photo')->nullable()->after('logo');
            $table->string('company_video_url')->nullable()->after('cover_photo');
            $table->json('office_photos')->nullable()->after('company_video_url');
            $table->json('product_images')->nullable()->after('office_photos');

            // Online Presence
            $table->string('youtube_channel')->nullable()->after('website');
            $table->string('instagram_profile')->nullable()->after('youtube_channel');

            // Notification Settings
            $table->boolean('email_notifications')->default(true)->after('auto_approval');
            $table->boolean('sms_notifications')->default(false)->after('email_notifications');
            $table->boolean('application_alerts')->default(true)->after('sms_notifications');
            $table->boolean('shortlist_alerts')->default(true)->after('application_alerts');
        });

        // ── 2. Create company_hrs table ──
        Schema::create('company_hrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('designation')->default('recruiter'); // admin, hr_manager, recruiter
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_hrs');

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'name_bn', 'tagline', 'company_type', 'business_registration_number', 'employee_count',
                'contact_person_name', 'contact_person_designation', 'contact_phone', 'contact_alt_phone', 'contact_email',
                'head_office_address', 'address_country', 'address_division', 'address_district', 'address_postal_code', 'google_map_embed',
                'services_products', 'working_culture',
                'hr_manager_name', 'hr_contact_number', 'hr_email', 'recruitment_policy', 'hiring_process',
                'allow_job_posting', 'job_posting_limit_monthly', 'featured_job_allowed', 'auto_approval', 'job_expiry_days',
                'nid_document', 'registration_certificate', 'tin_number', 'verification_status',
                'cover_photo', 'company_video_url', 'office_photos', 'product_images',
                'youtube_channel', 'instagram_profile',
                'email_notifications', 'sms_notifications', 'application_alerts', 'shortlist_alerts',
            ]);
        });
    }
};
