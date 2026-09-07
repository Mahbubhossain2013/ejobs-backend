<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateReference extends Model
{
    protected $fillable = [
        'user_id', 'name', 'designation', 'organization', 'phone', 'email',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
