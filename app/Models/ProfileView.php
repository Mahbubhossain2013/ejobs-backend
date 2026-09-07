<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileView extends Model
{
    protected $fillable = [
        'candidate_id',
        'employer_id',
        'recruiter_role',
        'is_anonymous',
        'view_count',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'view_count' => 'integer',
    ];

    /**
     * Relationship to the candidate being viewed.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /**
     * Relationship to the employer/recruiter viewing the profile.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
