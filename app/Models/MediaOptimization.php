<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaOptimization extends Model
{
    protected $fillable = [
        'original_name', 'unique_hash', 'original_size', 
        'optimized_size', 'saved_bytes', 'format', 'status'
    ];

    protected $casts = [
        'original_size' => 'integer',
        'optimized_size' => 'integer',
        'saved_bytes' => 'integer',
    ];
}
