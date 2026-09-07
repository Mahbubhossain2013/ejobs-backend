<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    protected $fillable = [
        'candidate_id',
        'employer_id',
        'job_id',
        'job_title',
        'company_name',
        'destination_country',
        'destination_city',
        'status',
        'expected_joining_date',
        'actual_joining_date',
        'notes',
    ];

    protected $casts = [
        'expected_joining_date' => 'date',
        'actual_joining_date' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(DeploymentStage::class)->orderBy('order');
    }

    public function getProgressAttribute(): int
    {
        $stages = $this->stages;
        if ($stages->isEmpty()) return 0;
        $completed = $stages->where('status', 'completed')->count();
        return round(($completed / $stages->count()) * 100);
    }

    public function getCurrentStageAttribute(): ?DeploymentStage
    {
        return $this->stages->firstWhere('status', 'in_progress')
            ?? $this->stages->firstWhere('status', 'pending');
    }
}
