<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BulkMessageBatch extends Model
{
    protected $fillable = [
        'employer_id', 'company_id', 'job_id', 'status_filter', 'channel', 'template',
        'subject', 'message', 'sms_message', 'interview_date', 'interview_location',
        'total_recipients', 'sent_count', 'failed_count', 'sms_count', 'sms_cost',
        'status_badge', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'sms_cost' => 'decimal:2',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BulkMessageRecipient::class, 'batch_id');
    }

    public function refreshCounts(): void
    {
        $sent = $this->recipients()->where('status', 'sent')->count();
        $failed = $this->recipients()->where('status', 'failed')->count();
        $skipped = $this->recipients()->where('status', 'skipped')->count();
        $queued = $this->recipients()->whereIn('status', ['pending', 'queued'])->count();

        $processed = $sent + $failed + $skipped;
        $total = $this->total_recipients ?: $this->recipients()->count();

        $badge = 'processing';
        if ($processed >= $total && $total > 0) {
            $badge = $failed > 0 ? 'partial_failed' : 'completed';
        } elseif ($processed === 0 && $queued > 0) {
            $badge = 'processing';
        }

        $this->timestamps = false;
        $this->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'sms_count' => (int) $this->recipients()->sum('sms_count'),
            'sms_cost' => (float) $this->recipients()->sum('sms_cost'),
            'status_badge' => $badge,
            'finished_at' => ($badge === 'completed' || $badge === 'partial_failed') ? now() : $this->finished_at,
        ]);
        $this->timestamps = true;
    }
}