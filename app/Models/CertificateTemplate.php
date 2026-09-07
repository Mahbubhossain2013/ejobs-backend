<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'orientation',
        'background_color', 'primary_color', 'accent_color',
        'logo_path', 'watermark_path', 'layout_config',
        'is_active', 'is_default',
    ];

    protected $casts = [
        'layout_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
