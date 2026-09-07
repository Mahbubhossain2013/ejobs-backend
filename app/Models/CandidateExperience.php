<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateExperience extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'designation', 'employment_type',
        'start_date', 'end_date', 'is_current', 'responsibilities', 'salary', 'order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
