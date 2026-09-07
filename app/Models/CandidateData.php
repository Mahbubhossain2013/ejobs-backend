<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateData extends Model
{
    protected $fillable = [
        'user_id', 'personal_info', 'skills', 'experience',
        'education', 'projects', 'social_links',
        'certifications', 'languages', 'awards', 'hobbies',
        'references', 'training',
    ];

    protected $casts = [
        'personal_info' => 'array',
        'skills' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'projects' => 'array',
        'social_links' => 'array',
        'certifications' => 'array',
        'languages' => 'array',
        'awards' => 'array',
        'hobbies' => 'array',
        'references' => 'array',
        'training' => 'array',
    ];
}
