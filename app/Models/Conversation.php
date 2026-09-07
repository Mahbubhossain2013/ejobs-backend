<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['uuid', 'job_id', 'employer_id', 'candidate_id', 'settings', 'muted_by', 'deleted_by'];

    protected $casts = [
        'settings' => 'array',
        'muted_by' => 'array',
        'deleted_by' => 'array',
    ];

    public function messages() { 
        return $this->hasMany(Message::class); 
    }
    
    public function job() { 
        return $this->belongsTo(Job::class); 
    }

    public function employer() {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function candidate() {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}