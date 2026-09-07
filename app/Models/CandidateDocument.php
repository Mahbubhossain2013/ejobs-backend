<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateDocument extends Model
{
    protected $fillable = [
        'user_id', 'type', 'label', 'file_path',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
