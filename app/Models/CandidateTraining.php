<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateTraining extends Model
{
    protected $fillable = [
        'user_id', 'title', 'institute_name', 'duration', 'year', 'certificate_path',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
