<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'role',
        'duration_days',
        'trial_days',
        'is_active',
        'is_visible',
        'is_popular',
        'monthly_credits',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'trial_days' => 'integer',
        'monthly_credits' => 'integer',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_popular' => 'boolean',
    ];

    /**
     * Relationship to PlanFeature values
     */
    public function featureValues(): HasMany
    {
        return $this->hasMany(FeatureValue::class);
    }

    /**
     * Relationship to active user subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Auto-slugify the plan name
     */
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($plan) {
            if (!$plan->slug) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    /**
     * Scope for active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for visible plans
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Safely duplicate this plan along with all its feature values
     */
    public function duplicate(string $newName = null): self
    {
        $newName = $newName ?: $this->name . ' (Copy)';
        
        $newPlan = $this->replicate([
            'slug'
        ]);
        $newPlan->name = $newName;
        $newPlan->slug = Str::slug($newName);
        $newPlan->is_active = false; // default copy to inactive for review
        $newPlan->save();

        foreach ($this->featureValues as $featureValue) {
            $newFeatureValue = $featureValue->replicate();
            $newPlan->featureValues()->save($newFeatureValue);
        }

        return $newPlan;
    }
}
