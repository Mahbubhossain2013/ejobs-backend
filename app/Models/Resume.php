<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Resume extends Model
{
    // Ensure all these fields are here
    protected $fillable = [
        'uuid', 'user_id', 'title', 'template_slug', 'data_snapshot',
        'parent_id', 'is_public', 'password', 'expires_at', 'views_count', 'theme_settings'
    ];
    
    protected $casts = [
        'data_snapshot' => 'array',
        'theme_settings' => 'array',
        'expires_at' => 'datetime',
        'is_public' => 'boolean'
    ];

    public function parent()
    {
        return $this->belongsTo(Resume::class, 'parent_id');
    }

    public function versions()
    {
        return $this->hasMany(Resume::class, 'parent_id')->orderBy('created_at', 'desc');
    }
}