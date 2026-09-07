<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillAssessmentAnswer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'user_answer',
        'is_correct', 'points_earned',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentQuestion::class, 'question_id');
    }
}
