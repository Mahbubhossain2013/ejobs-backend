<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = ['code', 'symbol', 'rate', 'enabled'];

    protected $casts = [
        'enabled' => 'boolean',
        'rate' => 'decimal:8',
    ];
}
