<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyUpdate extends Model
{
    protected $fillable = [
        'company_id',
        'content',
        'media_path',
        'likes_count',
        'comments_count',
        'shares_count',
    ];

    protected $casts = [
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CompanyUpdateReaction::class, 'update_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CompanyUpdateComment::class, 'update_id');
    }
}
