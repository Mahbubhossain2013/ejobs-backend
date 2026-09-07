<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMatchScore extends Model
{
    protected $fillable = [
        'user_id',
        'job_id',
        'score',
        'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
