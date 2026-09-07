<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_sandbox' => 'boolean',
        'status' => 'boolean',
        'config' => 'array',
    ];
}
