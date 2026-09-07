<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['name_en', 'name_bn', 'icon', 'parent_id', 'is_remote', 'is_active', 'is_highlighted'];

    protected $casts = [
        'is_remote' => 'boolean',
        'is_active' => 'boolean',
        'is_highlighted' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}