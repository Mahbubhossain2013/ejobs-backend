<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillAssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_id', 'question', 'type', 'options',
        'correct_answer', 'points', 'order',
    ];

    protected $casts = [
        'options' => 'array',
        'points' => 'integer',
        'order' => 'integer',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(SkillAssessment::class, 'assessment_id');
    }

    public function answers()
    {
        return $this->hasMany(SkillAssessmentAnswer::class, 'question_id');
    }
}
