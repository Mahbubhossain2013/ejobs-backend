<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAnalytic extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'query_string',
        'filters',
        'result_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'filters' => 'array',
        'result_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
