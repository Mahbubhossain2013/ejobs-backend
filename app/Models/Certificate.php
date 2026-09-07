<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'certificate_number', 'user_id', 'template_id', 'enrollment_id',
        'attempt_id', 'type', 'recipient_name', 'course_title',
        'description', 'score', 'issued_at', 'expires_at',
        'file_path', 'file_format', 'metadata', 'is_verified',
        'generated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'metadata' => 'array',
        'is_verified' => 'boolean',
        'generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(SkillEnrollment::class, 'enrollment_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(SkillAssessmentAttempt::class, 'attempt_id');
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'CERT-' . strtoupper(Str::random(4)) . '-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('certificate_number', $number)->exists());

        return $number;
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return strtotime($this->expires_at) < time();
    }

    public function getVerificationUrlAttribute(): string
    {
        return url("/verify/{$this->certificate_number}");
    }
}
