<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'job_id',
        'title',
        'description',
        'amount',
        'status',       // pending, in_progress, submitted, approved, rejected
        'deadline',
        'sort_order',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'deadline'     => 'date',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Get completion percentage for a job's milestones.
     */
    public static function completionPercentage(int $jobId): float
    {
        $total = static::where('job_id', $jobId)->count();
        if ($total === 0) return 0;

        $completed = static::where('job_id', $jobId)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        return round(($completed / $total) * 100, 1);
    }

    /**
     * Get total approved milestone amount for a job.
     */
    public static function approvedAmount(int $jobId): float
    {
        return (float) static::where('job_id', $jobId)
            ->where('status', 'approved')
            ->sum('amount');
    }
}
