<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCulturePhoto extends Model
{
    protected $table = 'company_culture_photos';

    protected $fillable = [
        'company_id',
        'file_path',
        'caption',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
