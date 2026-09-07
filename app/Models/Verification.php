<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'verification_type',
        'document_type',
        'document_path',
        'document_back_path',
        'nid_number',
        'dob',
        'phone',
        'email',
        'otp_code',
        'otp_expires_at',
        'ai_confidence_score',
        'ai_analysis_data',
        'ip_address',
        'device_fingerprint',
        'reminder_count',
        'last_reminder_sent_at',
        'notes',
        'status',
        'admin_notes',
        'verified_at',
        'reviewed_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'last_reminder_sent_at' => 'datetime',
        'dob' => 'date',
        'ai_analysis_data' => 'array',
        'nid_number' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
