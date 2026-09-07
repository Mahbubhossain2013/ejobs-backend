<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiringEmails extends Command
{
    protected $signature = 'subscriptions:send-expiring-emails {--days=3}';
    protected $description = 'Send expiring subscription warning emails to users';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'JobBazar');
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000'));

        $subscriptions = UserSubscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>=', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->with(['user', 'plan'])
            ->get();

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;

            if (!$user || !$plan) {
                continue;
            }

            $features = [];
            if ($plan->featureValues) {
                foreach ($plan->featureValues as $fv) {
                    if ($fv->feature && (float) $fv->value > 0) {
                        $features[] = $fv->feature->name . ': ' . $fv->value;
                    }
                }
            }

            if (empty($features)) {
                $features[] = 'Priority job applications';
                $features[] = 'Advanced analytics';
                $features[] = 'Premium support';
            }

            try {
                Mail::send('emails.subscription_expiring', [
                    'brandName' => $brandName,
                    'userName' => $user->name,
                    'planName' => $plan->name,
                    'expiryDate' => $subscription->expires_at->format('F j, Y'),
                    'features' => $features,
                    'renewUrl' => $frontendUrl . '/dashboard/subscription',
                ], function ($message) use ($user, $brandName) {
                    $message->to($user->email)
                            ->subject("{$brandName} — Your Subscription is Expiring Soon");
                });

                $sent++;
            } catch (\Throwable $e) {
                Log::error("Subscription expiring email failed for user {$user->id}: " . $e->getMessage());
            }
        }

        $this->info("Sent {$sent} subscription expiring emails out of {$subscriptions->count()} eligible.");
        Log::info("Subscription expiring emails: sent {$sent}/{$subscriptions->count()}");

        return 0;
    }
}
