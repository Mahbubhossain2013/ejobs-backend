<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'user_id', 'job_id', 'title', 'type', 'campaign_type', 'target_skills', 'target_categories',
        'target_location', 'target_devices', 'target_experience', 'target_behaviors',
        'daily_budget', 'total_budget', 'spent_amount', 'start_date', 'end_date', 'status', 
        'last_billed_at', 'impressions', 'clicks', 'relevance_score', 'ctr_score', 
        'rejection_reason', 'moderation_status',
        'is_pinned', 'ranking_override', 'relevance_override', 'bid_override', 'ctr_override', 'freshness_override',
        'spam_score', 'fraud_score', 'link_safety_score', 'content_policy_score', 'duplicate_score', 'anomaly_score',
        'ai_explanation', 'whitelisted_employer', 'is_suspended', 'fraud_reason', 'suspicious_activity_logs'
    ];

    protected $casts = [
        'target_skills' => 'array',
        'target_categories' => 'array',
        'target_behaviors' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_billed_at' => 'datetime',
        'relevance_score' => 'float',
        'ctr_score' => 'float',
        'is_pinned' => 'boolean',
        'whitelisted_employer' => 'boolean',
        'is_suspended' => 'boolean',
        'suspicious_activity_logs' => 'array',
        'relevance_override' => 'float',
        'bid_override' => 'float',
        'ctr_override' => 'float',
        'freshness_override' => 'float',
        'spam_score' => 'float',
        'fraud_score' => 'float',
        'link_safety_score' => 'float',
        'content_policy_score' => 'float',
        'duplicate_score' => 'float',
        'anomaly_score' => 'float',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}