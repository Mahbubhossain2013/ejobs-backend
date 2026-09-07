<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillAssessment extends Model
{
    protected $fillable = [
        'course_id', 'title', 'description', 'passing_score',
        'time_limit_minutes', 'max_attempts', 'questions_to_show',
        'shuffle_questions', 'show_results_immediately', 'is_active',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'time_limit_minutes' => 'integer',
        'max_attempts' => 'integer',
        'questions_to_show' => 'integer',
        'shuffle_questions' => 'boolean',
        'show_results_immediately' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(SkillCourse::class, 'course_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SkillAssessmentQuestion::class, 'assessment_id')->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(SkillAssessmentAttempt::class, 'assessment_id');
    }

    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }

    public function getAverageScoreAttribute(): float
    {
        return $this->attempts()
            ->where('status', 'completed')
            ->whereNotNull('score')
            ->avg('score') ?? 0;
    }
}
