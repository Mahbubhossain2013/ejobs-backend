<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAlgorithmSetting extends Model
{
    protected $fillable = [
        'skills_matching_weight',
        'saved_jobs_weight',
        'company_follows_weight',
        'recent_activity_weight',
        'location_relevance_weight',
        'premium_boosting_weight',
        
        'track_job_views',
        'track_job_saves',
        'track_applications',
        'track_profile_visits',
        'track_company_visits',
        'track_search_history',
        'track_scroll_depth',
        'track_click_patterns',
        'track_session_engagement',
    ];

    protected $casts = [
        'skills_matching_weight' => 'integer',
        'saved_jobs_weight' => 'integer',
        'company_follows_weight' => 'integer',
        'recent_activity_weight' => 'integer',
        'location_relevance_weight' => 'integer',
        'premium_boosting_weight' => 'integer',
        
        'track_job_views' => 'boolean',
        'track_job_saves' => 'boolean',
        'track_applications' => 'boolean',
        'track_profile_visits' => 'boolean',
        'track_company_visits' => 'boolean',
        'track_search_history' => 'boolean',
        'track_scroll_depth' => 'boolean',
        'track_click_patterns' => 'boolean',
        'track_session_engagement' => 'boolean',
    ];

    /**
     * Get the single active settings row
     */
    public static function getActive(): self
    {
        return self::firstOrCreate([], [
            'skills_matching_weight' => 40,
            'saved_jobs_weight' => 10,
            'company_follows_weight' => 10,
            'recent_activity_weight' => 10,
            'location_relevance_weight' => 10,
            'premium_boosting_weight' => 20,
            'track_job_views' => true,
            'track_job_saves' => true,
            'track_applications' => true,
            'track_profile_visits' => true,
            'track_company_visits' => true,
            'track_search_history' => true,
            'track_scroll_depth' => true,
            'track_click_patterns' => true,
            'track_session_engagement' => true,
        ]);
    }
}
