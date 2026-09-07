<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillEnrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'status', 'progress',
        'enrolled_at', 'completed_at', 'certificate_path', 'metadata',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'enrolled_at' => 'date',
        'completed_at' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(SkillCourse::class, 'course_id');
    }
}
