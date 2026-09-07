<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAward extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'issuer',
        'year',
        'description',
        'is_verified',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_verified' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
