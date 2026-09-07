<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\PlanFeature;
use App\Models\UserSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Get list of active and visible subscription plans with their features.
     * Filters dynamically based on request user's role.
     */
    public function getPlans(Request $request)
    {
        try {
            // Resolve role from query param first, fallback to auth user role
            $role = $request->query('role', null);
            if (!$role) {
                $user = Auth::guard('sanctum')->user();
                $role = $user ? ($user->getRoleNames()->first() ?? 'candidate') : 'candidate';
            }

            // Enforce standard candidate or employer slugs/roles
            if ($role !== 'employer' && $role !== 'admin') {
                $role = 'candidate';
            }

            $plans = SubscriptionPlan::active()
                ->visible()
                ->where('role', $role === 'admin' ? 'candidate' : $role) // Admins can manage candidates by default
                ->with(['featureValues.feature'])
                ->get()
                ->map(function ($plan) {
                    $features = [];
                    foreach ($plan->featureValues as $valRecord) {
                        if ($valRecord->feature) {
                            $features[$valRecord->feature->feature_key] = $valRecord->value;
                        }
                    }
                    $plan->features_mapped = $features;
                    return $plan;
                });

            $allFeatures = PlanFeature::all();

            return response()->json([
                'status' => true,
                'plans' => $plans,
                'features' => $allFeatures,
                'current_role' => $role
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Plans Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to load subscription plans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details of the active user's subscription, including dynamic remaining quotas.
     */
    public function getMySubscription(Request $request)
    {
        try {
            $user = $request->user();
            
            // Ensure wallet is initialized
            \App\Models\Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0]
            );

            $activeSub = $user->activeSubscription();
            
            // If no active subscription, synthesize a Free plan subscription so
            // the frontend always has a plan_id to work with.
            if (!$activeSub) {
                $role = $user->getRoleNames()->first() ?? 'candidate';
                if ($role !== 'employer' && $role !== 'admin') {
                    $role = 'candidate';
                }

                $freePlan = SubscriptionPlan::active()
                    ->visible()
                    ->where('role', $role === 'admin' ? 'candidate' : $role)
                    ->where('price', 0)
                    ->with(['featureValues.feature'])
                    ->first();

                if ($freePlan) {
                    $features = [];
                    foreach ($freePlan->featureValues as $valRecord) {
                        if ($valRecord->feature) {
                            $features[$valRecord->feature->feature_key] = $valRecord->value;
                        }
                    }
                    $freePlan->features_mapped = $features;

                    $activeSub = (object) [
                        'id' => 0,
                        'plan_id' => $freePlan->id,
                        'plan_name' => $freePlan->name,
                        'status' => 'active',
                        'start_date' => null,
                        'end_date' => null,
                        'billing_cycle' => $freePlan->billing_cycle,
                        'plan' => $freePlan,
                    ];
                }
            }
            
            $quotas = [];
            if ($activeSub) {
                if ($activeSub->id) {
                    $user->load('quotas');
                    $quotas = $user->quotas->where('user_subscription_id', $activeSub->id)->mapWithKeys(function ($q) use ($user) {
                        return [$q->feature_key => [
                            'used' => $q->used,
                            'max_limit' => $q->max_limit,
                            'remaining' => $user->getRemainingQuota($q->feature_key)
                        ]];
                    });
                } elseif ($activeSub->plan) {
                    $features = data_get($activeSub->plan, 'features_mapped', []);
                    foreach ($features as $key => $value) {
                        if (is_numeric($value)) {
                            $quotas[$key] = [
                                'used' => 0,
                                'max_limit' => (int)$value,
                                'remaining' => (int)$value,
                            ];
                        }
                    }
                }
            }

            $subscriptionData = $activeSub ? (array) $activeSub : null;
            if ($subscriptionData) {
                $subscriptionData['plan_name'] = $subscriptionData['plan_name'] ?? data_get($activeSub->plan ?? null, 'name');
                $subscriptionData['end_date'] = $subscriptionData['end_date'] ?? data_get($activeSub, 'expires_at');
            }

            return response()->json([
                'status' => true,
                'active_subscription' => $subscriptionData,
                'quotas' => $quotas
            ]);
        } catch (\Throwable $e) {
            Log::error('Get My Subscription Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve active subscription details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase/Subscribe to a plan using wallet balance. Enforces role-based plans matches.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        try {
            $user = $request->user();
            $plan = SubscriptionPlan::active()->findOrFail($request->plan_id);

            // Enforce role separation: Recruiters cannot buy candidate plans, candidates cannot buy recruiter plans.
            $role = $user->getRoleNames()->first() ?? 'candidate';
            
            if ($role === 'employer' && $plan->role !== 'employer') {
                return response()->json([
                    'status' => false,
                    'message' => 'Recruiters are only permitted to purchase Employer Subscription Plans.'
                ], 403);
            }

            if ($role === 'candidate' && $plan->role !== 'candidate') {
                return response()->json([
                    'status' => false,
                    'message' => 'Candidates are only permitted to purchase Candidate Subscription Plans.'
                ], 403);
            }

            // Ensure wallet exists for this user
            \App\Models\Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0, 'locked_balance' => 0]
            );

            // Reload wallet relation
            $user->load('wallet');

            $subscription = $user->subscribeTo($plan, 'wallet');

            return response()->json([
                'status' => true,
                'message' => 'Successfully subscribed to the ' . $plan->name . ' plan!',
                'subscription' => $subscription
            ]);
        } catch (\Throwable $e) {
            Log::error('Subscribe Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancel an active recurring subscription.
     */
    public function cancel(Request $request)
    {
        try {
            $user = $request->user();
            $activeSub = $user->activeSubscription();

            if (!$activeSub) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have an active subscription to cancel.'
                ], 400);
            }

            if ($activeSub->billing_cycle === 'lifetime' || $activeSub->billing_cycle === 'trial') {
                return response()->json([
                    'status' => false,
                    'message' => 'Trial and Lifetime memberships do not require cancellation.'
                ], 400);
            }

            if (!$activeSub->is_recurring) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your subscription is already canceled.'
                ], 400);
            }

            $activeSub->update([
                'is_recurring' => false,
                'canceled_at' => now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Your automatic subscription renewal has been canceled. You will continue to have access to features until your billing cycle expires.',
                'subscription' => $activeSub
            ]);
        } catch (\Throwable $e) {
            Log::error('Cancel Subscription Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 400);
        }
    }
}
