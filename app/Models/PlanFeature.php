<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanFeature extends Model
{
    protected $fillable = [
        'name',
        'feature_key',
        'description',
        'type',
        'role',
    ];

    /**
     * Relationship to specific values set in plans
     */
    public function values(): HasMany
    {
        return $this->hasMany(FeatureValue::class);
    }
}
