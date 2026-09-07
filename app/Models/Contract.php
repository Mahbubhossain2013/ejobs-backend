<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contract extends Model
{
    protected $fillable = [
        'job_id', 'employer_id', 'candidate_id', 'job_application_id',
        'title', 'job_title_custom', 'category_id', 'project_scope',
        'budget', 'platform_fee', 'total_amount',
        'delivery_date', 'status', 'offer_status',
        'is_remote_project', 'deliverables', 'budget_type',
        'ownership_clause', 'confidentiality_clause', 'penalty_clause',
        'dispute_clause', 'additional_terms',
        'employer_otp', 'employer_signed_at', 'candidate_otp', 'candidate_signed_at',
        'employer_otp_expires_at', 'candidate_otp_expires_at',
        'offer_sent_at', 'offer_expires_at',
        'rejection_reason', 'terminated_at', 'meta_data', 'revision_history',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_date' => 'date',
        'employer_signed_at' => 'datetime',
        'candidate_signed_at' => 'datetime',
        'employer_otp_expires_at' => 'datetime',
        'candidate_otp_expires_at' => 'datetime',
        'terminated_at' => 'datetime',
        'offer_sent_at' => 'datetime',
        'offer_expires_at' => 'datetime',
        'is_remote_project' => 'boolean',
        'meta_data' => 'array',
        'deliverables' => 'array',
        'revision_history' => 'array',
    ];

    protected $hidden = [
        'employer_otp',
        'candidate_otp',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ContractRevision::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(Escrow::class, 'job_id', 'job_id');
    }

    public function isFullySigned(): bool
    {
        return $this->employer_signed_at !== null && $this->candidate_signed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->offer_expires_at && $this->offer_expires_at->isPast();
    }

    public function displayTitle(): string
    {
        return $this->job_title_custom ?? $this->job->title ?? $this->title;
    }
}
