<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = [
        'job_id', 'user_id', 'resume_path', 'cover_letter', 'delivery_days',
        'expected_salary', 'portfolio_link', 'status', 'meta_data'
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}