<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillLesson extends Model
{
    protected $fillable = [
        'course_id', 'title', 'content', 'video_url', 'order', 'duration_minutes',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(SkillCourse::class, 'course_id');
    }
}
