<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBrochure extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'file_path',
        'download_count',
    ];

    protected $casts = [
        'download_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
