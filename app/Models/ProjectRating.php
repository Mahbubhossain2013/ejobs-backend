<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRating extends Model
{
    protected $fillable = [
        'job_id', 'contract_id', 'rater_id', 'rated_id', 'rater_role',
        'skill_rating', 'communication_rating', 'delivery_rating',
        'professionalism_rating', 'comment', 'is_public',
    ];

    protected $casts = [
        'skill_rating' => 'integer',
        'communication_rating' => 'integer',
        'delivery_rating' => 'integer',
        'professionalism_rating' => 'integer',
        'overall_rating' => 'float',
        'is_public' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rated(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_id');
    }
}
