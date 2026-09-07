<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureValue extends Model
{
    protected $fillable = [
        'subscription_plan_id',
        'plan_feature_id',
        'value',
    ];

    /**
     * Relationship to the plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Relationship to the feature
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(PlanFeature::class, 'plan_feature_id');
    }
}
