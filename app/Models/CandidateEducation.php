<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEducation extends Model
{
    protected $table = 'candidate_educations';

    protected $fillable = [
        'user_id', 'level', 'board', 'group_or_subject', 'degree_name',
        'institute_name', 'passing_year', 'gpa_or_cgpa', 'order',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
