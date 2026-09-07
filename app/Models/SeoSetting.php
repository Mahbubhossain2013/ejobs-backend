<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = [
        'page_key',
        'meta_title',
        'meta_description',
        'og_image_path',
        'structured_data',
    ];

    protected $casts = [
        'meta_title' => 'array',
        'meta_description' => 'array',
        'structured_data' => 'array',
    ];
}
