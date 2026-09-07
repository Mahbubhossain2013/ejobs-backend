<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateInterview extends Model
{
    protected $table = 'candidate_interviews';

    protected $fillable = [
        'user_id',
        'interview_type',
        'history',
        'ai_score',
        'weak_areas',
        'practice_topics',
        'communication_feedback',
    ];

    protected $casts = [
        'history' => 'array',
        'ai_score' => 'integer',
        'weak_areas' => 'array',
        'practice_topics' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
