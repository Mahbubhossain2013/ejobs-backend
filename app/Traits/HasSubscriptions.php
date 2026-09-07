<?php

namespace App\Traits;

use App\Models\UserSubscription;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use App\Models\UserSubscriptionQuota;
use App\Models\Badge;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

trait HasSubscriptions
{
    /**
     * Relationship to all user subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Relationship to user subscription quotas
     */
    public function quotas(): HasMany
    {
        return $this->hasMany(UserSubscriptionQuota::class);
    }

    /**
     * Get the user's current active subscription (and clean up expired badges if none)
     */
    public function activeSubscription(): ?UserSubscription
    {
        $activeSub = $this->subscriptions()->active()->first();

        if (!$activeSub) {
            // Self-repairing premium badge cleanup: if subscription is expired, revoke temporary premium badges
            $this->revokeBadge('premium');
            $this->revokeBadge('pro');
        }

        return $activeSub;
    }

    /**
     * Check if the user has access to a specific premium feature
     */
    public function hasFeature(string $featureKey): bool
    {
        $value = $this->getFeatureValue($featureKey);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            // For numeric limits, check if they have remaining quota
            $remaining = $this->getRemainingQuota($featureKey);
            return $remaining > 0;
        }

        if (is_string($value)) {
            return $value === 'true' || ($value !== 'false' && !empty($value));
        }

        return false;
    }

    /**
     * Get the value of a specific subscription feature
     */
    public function getFeatureValue(string $featureKey, $default = null)
    {
        $activeSub = $this->activeSubscription();

        if ($activeSub && isset($activeSub->plan_details['features'])) {
            $features = $activeSub->plan_details['features'];
            if (array_key_exists($featureKey, $features)) {
                return $this->castFeatureValue($features[$featureKey]);
            }
        }

        // Fallback to the appropriate "Free" plan configurations based on the user's role
        $role = $this->getRoleNames()->first() ?? 'candidate';
        $freeSlug = $role === 'employer' ? 'employer-free' : 'free';

        $freePlan = SubscriptionPlan::active()
            ->where('slug', $freeSlug)
            ->first();

        // Secondary fallback to any free plan
        if (!$freePlan) {
            $freePlan = SubscriptionPlan::active()
                ->where(function($q) {
                    $q->where('price', 0.00)
                      ->orWhere('billing_cycle', 'lifetime');
                })
                ->first();
        }

        if ($freePlan) {
            $feature = PlanFeature::where('feature_key', $featureKey)->first();
            if ($feature) {
                $valRecord = $freePlan->featureValues()->where('plan_feature_id', $feature->id)->first();
                if ($valRecord) {
                    return $this->castFeatureValue($valRecord->value);
                }
            }
        }

        return $default;
    }

    /**
     * Cast the raw string feature value to proper PHP types
     */
    protected function castFeatureValue(string $value)
    {
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $value;
    }

    /**
     * Get the remaining quota for a numerical feature
     */
    public function getRemainingQuota(string $featureKey): int
    {
        $activeSub = $this->activeSubscription();

        if ($activeSub) {
            $quota = $this->quotas()
                ->where('user_subscription_id', $activeSub->id)
                ->where('feature_key', $featureKey)
                ->first();

            if ($quota) {
                if ($quota->max_limit >= 9999 || $quota->max_limit < 0) {
                    return 9999; // Unlimited
                }
                return max(0, $quota->max_limit - $quota->used);
            }

            // If subscription is active but quota isn't saved in DB yet, check snapshot
            if (isset($activeSub->plan_details['features'][$featureKey])) {
                $snapVal = $activeSub->plan_details['features'][$featureKey];
                if (is_numeric($snapVal)) {
                    return (int)$snapVal;
                }
                if ($snapVal === 'true') {
                    return 9999;
                }
            }
        }

        // Fallback: Check the Free plan configurations if no active subscription
        $role = $this->getRoleNames()->first() ?? 'candidate';
        $freeSlug = $role === 'employer' ? 'employer-free' : 'free';

        $freePlan = SubscriptionPlan::active()
            ->where('slug', $freeSlug)
            ->first();

        if ($freePlan) {
            $feature = PlanFeature::where('feature_key', $featureKey)->first();
            if ($feature) {
                $valRecord = $freePlan->featureValues()->where('plan_feature_id', $feature->id)->first();
                if ($valRecord && is_numeric($valRecord->value)) {
                    return (int)$valRecord->value;
                }
            }
        }

        return 0; // Default fallback to 0 limit if not configured
    }

    /**
     * Increment/Consume a feature quota limit securely inside lock transaction
     */
    public function useFeatureQuota(string $featureKey, int $amount = 1): bool
    {
        $activeSub = $this->activeSubscription();

        if (!$activeSub) {
            // Free plan limits: allow if free remaining limit >= amount
            $remaining = $this->getRemainingQuota($featureKey);
            return $remaining >= $amount;
        }

        return DB::transaction(function () use ($activeSub, $featureKey, $amount) {
            $quota = $this->quotas()
                ->where('user_subscription_id', $activeSub->id)
                ->where('feature_key', $featureKey)
                ->lockForUpdate()
                ->first();

            if (!$quota) {
                $maxLimit = 0;
                if (isset($activeSub->plan_details['features'][$featureKey])) {
                    $snapVal = $activeSub->plan_details['features'][$featureKey];
                    $maxLimit = is_numeric($snapVal) ? (int)$snapVal : ($snapVal === 'true' ? 9999 : 0);
                }

                $quota = UserSubscriptionQuota::create([
                    'user_id' => $this->id,
                    'user_subscription_id' => $activeSub->id,
                    'feature_key' => $featureKey,
                    'used' => 0,
                    'max_limit' => $maxLimit,
                ]);
            }

            if ($quota->max_limit >= 9999 || $quota->max_limit < 0) {
                // Unlimited feature
                $quota->increment('used', $amount);
                return true;
            }

            if ($quota->used + $amount > $quota->max_limit) {
                return false; // Quota limit reached/exceeded!
            }

            $quota->increment('used', $amount);
            return true;
        });
    }

    /**
     * Get active badges sorted by priority
     */
    public function activeBadges()
    {
        return $this->badges()
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where('is_visible', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Assign a badge to the user
     */
    public function assignBadge(string $key, string $assignedBy = 'system', $expiresAt = null): void
    {
        $badge = Badge::where('badge_key', $key)->first();
        if ($badge) {
            $this->badges()->syncWithoutDetaching([
                $badge->id => [
                    'assigned_by' => $assignedBy,
                    'expires_at' => $expiresAt,
                    'is_visible' => true,
                ]
            ]);
        }
    }

    /**
     * Revoke a badge from the user
     */
    public function revokeBadge(string $key): void
    {
        $badge = Badge::where('badge_key', $key)->first();
        if ($badge) {
            $this->badges()->detach($badge->id);
        }
    }

    /**
     * Check if the user has a badge
     */
    public function hasBadge(string $key): bool
    {
        return $this->activeBadges()->contains('badge_key', $key);
    }

    /**
     * Subscribe the user to a plan, charging their wallet balance
     */
    public function subscribeTo(SubscriptionPlan $plan, string $paymentMethod = 'wallet'): UserSubscription
    {
        if (!$this->wallet) {
            throw new Exception("User does not have an active wallet.");
        }

        return DB::transaction(function () use ($plan, $paymentMethod) {
            // Calculate remaining credit from any active subscription (upgrade path)
            $remainingCredit = 0.00;
            $activeSub = $this->activeSubscription();
            if ($activeSub && $activeSub->starts_at && $activeSub->expires_at) {
                $oldPrice = (float)data_get($activeSub->plan_details, 'price', data_get($activeSub->plan, 'price', 0));
                
                if ($oldPrice > 0.00) {
                    $totalDays = Carbon::parse($activeSub->starts_at)->diffInDays(Carbon::parse($activeSub->expires_at)) ?: 1;
                    $usedDays = Carbon::parse($activeSub->starts_at)->diffInDays(Carbon::now());
                    
                    if ($usedDays < 0) $usedDays = 0;
                    if ($usedDays > $totalDays) $usedDays = $totalDays;
                    
                    $perDayCost = $oldPrice / $totalDays;
                    $usedCost = $usedDays * $perDayCost;
                    $remainingCredit = max(0.00, $oldPrice - $usedCost);
                    
                    // Cap it to the new plan's price
                    $remainingCredit = min($remainingCredit, (float)$plan->price);
                }
            }

            // 1. Charge standard plan price minus remaining credit (if net price > 0)
            $netPrice = max(0.00, (float)$plan->price - $remainingCredit);
            
            if ($netPrice > 0.00) {
                if ($this->wallet->balance < $netPrice) {
                    throw new Exception("Insufficient wallet balance. Please add BDT " . number_format($netPrice - $this->wallet->balance, 2) . " more to purchase this plan.");
                }
                
                // Debit wallet
                $this->wallet->debit(
                    (float)$netPrice,
                    UserSubscription::class,
                    null, // we will update this with subscription ID later
                    "Subscription purchase: " . $plan->name . ($remainingCredit > 0 ? " (includes credit: BDT " . number_format($remainingCredit, 2) . ")" : "")
                );
            }

            // 2. Terminate any other currently active subscription
            $this->subscriptions()->active()->update([
                'status' => 'expired',
                'is_recurring' => false
            ]);

            // 3. Calculate expiration date based on cycle
            $startsAt = Carbon::now();
            $expiresAt = null;

            switch ($plan->billing_cycle) {
                case 'monthly':
                    $expiresAt = $startsAt->copy()->addDays($plan->duration_days ?: 30);
                    break;
                case 'yearly':
                    $expiresAt = $startsAt->copy()->addDays($plan->duration_days ?: 365);
                    break;
                case 'trial':
                    $expiresAt = $startsAt->copy()->addDays($plan->trial_days ?: 14);
                    break;
                case 'lifetime':
                default:
                    $expiresAt = null;
                    break;
            }

            // 4. Archive plan details with feature values snapshot
            $featureSnapshot = [];
            $plan->load('featureValues.feature');
            foreach ($plan->featureValues as $valRecord) {
                if ($valRecord->feature) {
                    $featureSnapshot[$valRecord->feature->feature_key] = $valRecord->value;
                }
            }

            $planDetailsSnapshot = [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'billing_cycle' => $plan->billing_cycle,
                'features' => $featureSnapshot
            ];

            // 5. Create new subscription
            $subscription = UserSubscription::create([
                'user_id' => $this->id,
                'subscription_plan_id' => $plan->id,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'billing_cycle' => $plan->billing_cycle,
                'payment_status' => $plan->price > 0.00 ? 'paid' : 'free',
                'status' => 'active',
                'is_recurring' => $plan->billing_cycle !== 'trial', // trials don't auto-renew
                'plan_details' => $planDetailsSnapshot
            ]);

            // 6. Initialize Quotas for numerical features
            foreach ($plan->featureValues as $valRecord) {
                if ($valRecord->feature && is_numeric($valRecord->value)) {
                    UserSubscriptionQuota::create([
                        'user_id' => $this->id,
                        'user_subscription_id' => $subscription->id,
                        'feature_key' => $valRecord->feature->feature_key,
                        'used' => 0,
                        'max_limit' => (int)$valRecord->value,
                    ]);
                }
            }

            // 7. Update reference id on wallet transaction if paid
            if (isset($netPrice) && $netPrice > 0.00) {
                $lastTx = $this->wallet->transactions()
                    ->where('type', 'debit')
                    ->where('reference_type', UserSubscription::class)
                    ->whereNull('reference_id')
                    ->first();
                if ($lastTx) {
                    $lastTx->update(['reference_id' => $subscription->id]);
                }
            }

            // 8. Dynamic Badge Synchronization
            // Revoke active subscription badges to prevent overlap
            $this->revokeBadge('premium');
            $this->revokeBadge('pro');

            // Assign matching badge based on subscription tier/slug keywords
            if (str_contains($plan->slug, 'premium')) {
                $this->assignBadge('premium');
            } elseif (str_contains($plan->slug, 'pro')) {
                $this->assignBadge('pro');
            }

            return $subscription;
        });
    }
}
