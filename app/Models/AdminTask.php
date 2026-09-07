<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminTask extends Model
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'is_completed',
    ];
}
