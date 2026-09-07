<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Escrow extends Model
{
    protected $fillable = ['job_id', 'contract_id', 'employer_id', 'candidate_id', 'amount', 'platform_fee', 'status'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function dispute()
    {
        return $this->hasOne(Dispute::class, 'job_id', 'job_id');
    }
}