<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUpdateReaction extends Model
{
    protected $table = 'company_update_reactions';

    protected $fillable = [
        'user_id',
        'update_id',
        'reaction_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function companyUpdate(): BelongsTo
    {
        return $this->belongsTo(CompanyUpdate::class, 'update_id');
    }
}
