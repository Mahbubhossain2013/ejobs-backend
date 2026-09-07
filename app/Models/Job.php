<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $with = ['company', 'category'];

    protected $fillable = [
        'company_id',
        'category_id',
        'title',
        'slug',
        'description',
        'salary_range',
        'salary_min',
        'salary_max',
        'budget',
        'budget_type',
        'required_skills',
        'project_duration',
        'experience_level',
        'timezone',
        'country_restriction',
        'language_requirement',
        'visibility',
        'job_type',
        'is_remote_project',
        'location',
        'division',
        'district',
        'upazila',
        'deadline',
        'is_active',
        'project_status',
        'assigned_to',
        // Section 1: Basic Job Information
        'vacancies',
        'workplace_type',
        'salary_type',
        // Section 2: Company / Contact
        'contact_person_name',
        'contact_email',
        'contact_phone',
        // Section 3: Granular description
        'job_summary',
        'responsibilities',
        'education_requirements',
        'experience_requirements',
        'additional_requirements',
        'benefits',
        // Section 4: Candidate Requirements
        'min_age',
        'max_age',
        'gender_preference',
        'language_skills',
        'required_certifications',
        'driving_license_required',
        // Section 5: Application Info
        'application_method',
        'application_url',
        'application_email',
        'required_documents',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'is_remote_project' => 'boolean',
        'is_active' => 'boolean',
        'deadline' => 'date',
        'budget' => 'float',
        'vacancies' => 'integer',
        'min_age' => 'integer',
        'max_age' => 'integer',
        'driving_license_required' => 'boolean',
        'language_skills' => 'array',
        'required_certifications' => 'array',
        'required_documents' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function workDiaries(): HasMany
    {
        return $this->hasMany(WorkDiary::class);
    }
}
