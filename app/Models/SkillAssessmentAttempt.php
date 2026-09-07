<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillAssessmentAttempt extends Model
{
    protected $fillable = [
        'user_id', 'assessment_id', 'status', 'score',
        'total_points', 'is_passed', 'started_at', 'completed_at',
        'time_spent_seconds',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_points' => 'integer',
        'is_passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(SkillAssessment::class, 'assessment_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SkillAssessmentAnswer::class, 'attempt_id');
    }

    public function isTimedOut(): bool
    {
        if (!$this->assessment->time_limit_minutes || $this->status !== 'in_progress') {
            return false;
        }
        return $this->started_at->addMinutes($this->assessment->time_limit_minutes)->isPast();
    }

    public function getRemainingTimeSecondsAttribute(): ?int
    {
        if (!$this->assessment->time_limit_minutes || $this->status !== 'in_progress') {
            return null;
        }
        $endTime = $this->started_at->copy()->addMinutes($this->assessment->time_limit_minutes);
        $remaining = $endTime->diffInSeconds(now(), false);
        return max(0, $remaining);
    }
}
