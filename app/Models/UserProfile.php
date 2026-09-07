<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'skills' => 'array', 'experience' => 'array', 'education' => 'array',
        'projects' => 'array', 'interests' => 'array', 'notification_settings' => 'array',
        'trust_score' => 'integer', 'rating' => 'float', 'completed_jobs_count' => 'integer',
        'total_earnings' => 'float', 'is_featured' => 'boolean',
        'profile_completion_percentage' => 'integer', 'trust_explanations' => 'array',
        'profile_strength_breakdown' => 'array', 'ai_risk_score' => 'integer',
        'ai_quality_score' => 'integer', 'manual_override_score' => 'integer',
        'manual_override_status' => 'string', 'behavior_summary' => 'string',
        'login_device_history' => 'array', 'active_sessions' => 'array',
        'ban_status' => 'boolean',
        'social_links' => 'array',
        // New
        'computer_skills' => 'array', 'microsoft_office_level' => 'array',
        'other_skills' => 'array', 'language_proficiency' => 'array',
        'application_tracking' => 'array', 'available_remote' => 'boolean',
        'available_relocation' => 'boolean', 'one_click_apply' => 'boolean',
        'is_verified' => 'boolean',
        'job_alert_enabled' => 'boolean',
    ];

    public function getDateOfBirthAttribute($value)
    {
        if (!$value) return null;
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function user() { return $this->belongsTo(User::class); }
    public function educations() { return $this->hasMany(CandidateEducation::class, 'user_id'); }
    public function experiences() { return $this->hasMany(CandidateExperience::class, 'user_id'); }
    public function trainings() { return $this->hasMany(CandidateTraining::class, 'user_id'); }
    public function candidateCertifications() { return $this->hasMany(CandidateCertification::class, 'user_id'); }
    public function candidateReferences() { return $this->hasMany(CandidateReference::class, 'user_id'); }
    public function documents() { return $this->hasMany(CandidateDocument::class, 'user_id'); }
}
