<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkDiary extends Model
{
    protected $fillable = [
        'job_id',
        'user_id',
        'date',
        'hours_worked',
        'description',
        'attachments',
        'status',       // submitted, approved, rejected
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'date'         => 'date',
        'hours_worked' => 'decimal:2',
        'reviewed_at'  => 'datetime',
        'attachments'  => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get total approved hours for a job.
     */
    public static function approvedHoursForJob(int $jobId): float
    {
        return (float) static::where('job_id', $jobId)
            ->where('status', 'approved')
            ->sum('hours_worked');
    }

    /**
     * Get weekly summary for a job.
     */
    public static function weeklySummary(int $jobId, int $userId): array
    {
        $entries = static::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->where('date', '>=', now()->startOfWeek())
            ->where('date', '<=', now()->endOfWeek())
            ->orderBy('date')
            ->get();

        return [
            'entries'       => $entries,
            'total_hours'   => (float) $entries->sum('hours_worked'),
            'approved_hours' => (float) $entries->where('status', 'approved')->sum('hours_worked'),
            'pending_hours'  => (float) $entries->where('status', 'submitted')->sum('hours_worked'),
        ];
    }
}
