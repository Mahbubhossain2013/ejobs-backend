<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'preview_image_path', 'is_premium',
        'price', 'monetization_model', 'is_active',
        'html_content', 'css_content', 'meta_schema', 'is_featured',
        'category', 'ats_compatible', 'is_ats_friendly', 'dark_mode_supported',
        'version', 'author', 'credits_required'
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'ats_compatible' => 'boolean',
        'is_ats_friendly' => 'boolean',
        'dark_mode_supported' => 'boolean',
        'meta_schema' => 'array',
        'credits_required' => 'integer',
    ];
}