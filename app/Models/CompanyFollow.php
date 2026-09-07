<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFollow extends Model
{
    protected $fillable = ['user_id', 'company_id'];

    /**
     * Relationship to the user who followed (candidate)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the company being followed
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
