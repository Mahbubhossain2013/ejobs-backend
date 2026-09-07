<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $with = ['user'];

    protected $fillable = [
        'user_id', 'name', 'name_bn', 'slug', 'logo', 'cover_photo', 'tagline',
        'description', 'website', 'industry', 'company_type', 'location', 'city',
        'facebook', 'linkedin', 'profile_views_count', 'response_rate',
        'is_verified', 'trade_license_number', 'trade_license_document',
        'business_registration_number', 'founded_year', 'size', 'employee_count',
        'mission', 'vision', 'values', 'why_join_us', 'top_skills',
        'services_products', 'working_culture',
        // Contact
        'contact_person_name', 'contact_person_designation', 'contact_phone',
        'contact_alt_phone', 'contact_email',
        // Address
        'head_office_address', 'address_country', 'address_division',
        'address_district', 'address_postal_code', 'google_map_embed',
        // HR
        'hr_manager_name', 'hr_contact_number', 'hr_email',
        'recruitment_policy', 'hiring_process',
        // Job Posting Settings
        'allow_job_posting', 'job_posting_limit_monthly', 'featured_job_allowed',
        'auto_approval', 'job_expiry_days',
        // Verification
        'nid_document', 'registration_certificate', 'tin_number', 'verification_status',
        // Media
        'company_video_url', 'office_photos', 'product_images',
        // Online Presence
        'youtube_channel', 'instagram_profile',
        // Notifications
        'email_notifications', 'sms_notifications', 'application_alerts', 'shortlist_alerts',
        // Legacy / Trust
        'highlights', 'is_featured', 'trust_score', 'rating',
        'completed_jobs_count', 'total_spend', 'reputation_status',
        'profile_completion_percentage', 'trust_explanations', 'profile_strength_breakdown',
        'ai_risk_score', 'ai_quality_score', 'manual_override_score',
        'manual_override_status', 'override_reason', 'behavior_summary',
        'last_login_ip', 'login_device_history', 'active_sessions',
        'restriction_status', 'ban_status', 'moderation_notes',
    ];

    protected $casts = [
        'highlights' => 'array',
        'why_join_us' => 'array',
        'top_skills' => 'array',
        'office_photos' => 'array',
        'product_images' => 'array',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'allow_job_posting' => 'boolean',
        'featured_job_allowed' => 'boolean',
        'auto_approval' => 'boolean',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'application_alerts' => 'boolean',
        'shortlist_alerts' => 'boolean',
        'trust_score' => 'integer',
        'rating' => 'float',
        'completed_jobs_count' => 'integer',
        'total_spend' => 'float',
        'profile_completion_percentage' => 'integer',
        'trust_explanations' => 'array',
        'profile_strength_breakdown' => 'array',
        'ai_risk_score' => 'integer',
        'ai_quality_score' => 'integer',
        'manual_override_score' => 'integer',
        'manual_override_status' => 'string',
        'behavior_summary' => 'string',
        'login_device_history' => 'array',
        'active_sessions' => 'array',
        'ban_status' => 'boolean',
    ];

    /**
     * Boot method to auto-generate missing slugs before saving to DB
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                // Generates a URL-friendly slug like: "tech-corp-1682390123"
                $company->slug = Str::slug($company->name) . '-' . time();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(CompanyFollow::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class)->latest();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CompanyUpdate::class)->latest();
    }

    public function brochures(): HasMany
    {
        return $this->hasMany(CompanyBrochure::class)->latest();
    }

    public function culturePhotos(): HasMany
    {
        return $this->hasMany(CompanyCulturePhoto::class)->latest();
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CompanyAward::class)->latest();
    }

    public function hrTeam(): HasMany
    {
        return $this->hasMany(CompanyHr::class);
    }
}
