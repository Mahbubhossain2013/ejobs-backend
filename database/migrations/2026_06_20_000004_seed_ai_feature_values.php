<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plans = DB::table('subscription_plans')->get();
        $features = DB::table('plan_features')->get();

        foreach ($plans as $plan) {
            $isFree = $plan->price == 0 || $plan->billing_cycle === 'lifetime';
            $isPro = str_contains(strtolower($plan->name), 'pro') || str_contains(strtolower($plan->name), 'enterprise');
            $isEmployer = $plan->role === 'employer';

            foreach ($features as $feature) {
                // Skip features that already have values
                $exists = DB::table('feature_values')
                    ->where('subscription_plan_id', $plan->id)
                    ->where('plan_feature_id', $feature->id)
                    ->exists();

                if ($exists) continue;

                $value = '0';

                switch ($feature->feature_key) {
                    case 'ai_cv_builder':
                    case 'certificate_generation':
                        $value = ($isFree) ? '0' : '1';
                        break;

                    case 'ai_chat_messages':
                    case 'ai_cover_letters':
                    case 'ai_resume_score':
                        if ($isFree) $value = '0';
                        elseif ($isPro) $value = '9999';
                        else $value = '30';
                        break;

                    case 'ai_skill_assessment':
                    case 'ai_interview_prep':
                        if ($isFree) $value = '0';
                        elseif ($isPro) $value = '9999';
                        else $value = '2';
                        break;

                    default:
                        continue 2;
                }

                DB::table('feature_values')->insert([
                    'subscription_plan_id' => $plan->id,
                    'plan_feature_id' => $feature->id,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('feature_values')->whereIn('plan_feature_id', function ($query) {
            $query->select('id')->from('plan_features')->whereIn('feature_key', [
                'ai_cv_builder', 'ai_skill_assessment', 'certificate_generation', 'ai_interview_prep'
            ]);
        })->delete();
    }
};
