<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutGateway extends Model
{
    // Add all columns here to allow saving from Admin Panel
    protected $fillable = [
        'name',
        'logo',
        'min_amount',
        'max_amount',
        'fixed_charge',
        'percent_charge',
        'user_input',
        'is_active'
    ];

    protected $casts = [
        'user_input' => 'array',
        'is_active' => 'boolean',
        'min_amount' => 'decimal:2',
        'percent_charge' => 'decimal:2',
    ];
}