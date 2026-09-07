<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    protected $fillable = [
        'job_id',
        'opened_by',
        'reason',
        'proof_path',
        'refund_percentage',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'refund_percentage' => 'float',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
