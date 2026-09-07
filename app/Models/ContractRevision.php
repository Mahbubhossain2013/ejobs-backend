<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRevision extends Model
{
    protected $fillable = [
        'contract_id', 'revised_by', 'changes_summary',
        'previous_data', 'new_data', 'requires_candidate_approval',
        'status', 'rejection_reason', 'approved_at',
    ];

    protected $casts = [
        'previous_data' => 'array',
        'new_data' => 'array',
        'requires_candidate_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
