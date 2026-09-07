<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateCertification extends Model
{
    protected $fillable = [
        'user_id', 'name', 'organization', 'issue_date', 'expiry_date', 'certificate_path',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
