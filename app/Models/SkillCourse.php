<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillCourse extends Model
{
    protected $fillable = [
        'title', 'description', 'category', 'difficulty', 'duration_hours',
        'price', 'currency', 'instructor_name', 'thumbnail_path', 'is_active',
        'is_certified', 'enrollment_count', 'max_enrollments',
        'ai_question_count', 'ai_question_type',
        'certificate_template_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_certified' => 'boolean',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(SkillEnrollment::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SkillLesson::class, 'course_id')->orderBy('order');
    }

    public function assessment()
    {
        return $this->hasOne(SkillAssessment::class, 'course_id');
    }

    public function certificates()
    {
        return $this->hasManyThrough(Certificate::class, SkillEnrollment::class, 'course_id', 'enrollment_id');
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }
}
