<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyReview extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'rating', 'comment', 'is_anonymous', 'status',
        'rating_work_culture', 'rating_salary', 'rating_management',
        'rating_growth', 'rating_work_life_balance',
        'ai_toxicity_score', 'ai_fake_probability', 'ai_duplicate_score',
        'moderation_details',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'rating' => 'integer',
        'moderation_details' => 'array',
    ];

    /**
     * Relationship to the user who wrote the review (candidate)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the company being reviewed
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
