<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'category_bn',
        'title',
        'title_bn',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'date',
    ];
}
