<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Log;

class SubscriptionBillingService
{
    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Generate a subscription invoice on purchase/activation.
     */
    public function generateOnPurchase(UserSubscription $subscription): Invoice
    {
        $subscription->loadMissing(['user', 'plan']);
        $plan = $subscription->plan;
        $user = $subscription->user;

        $invoice = $this->invoiceService->createInvoice([
            'type'           => 'subscription',
            'user_id'        => $user->id,
            'reference_type' => UserSubscription::class,
            'reference_id'   => $subscription->id,
            'currency_code'  => 'USD',
            'due_date'       => now()->toDateString(),
            'notes'          => "Subscription: {$plan->name} ({$subscription->billing_cycle})",
        ], [
            [
                'description' => "{$plan->name} Subscription",
                'details'     => ucfirst($subscription->billing_cycle) . ' billing cycle',
                'quantity'    => 1,
                'unit_price'  => $plan->price,
                'type'        => 'service',
            ],
        ]);

        Log::info("Subscription invoice generated: #{$invoice->invoice_number} for user #{$user->id}");
        return $invoice;
    }

    /**
     * Generate a renewal invoice for a recurring subscription.
     */
    public function generateRenewalInvoice(UserSubscription $subscription): Invoice
    {
        $subscription->loadMissing(['user', 'plan']);
        $plan = $subscription->plan;
        $user = $subscription->user;

        $invoice = $this->invoiceService->createInvoice([
            'type'           => 'subscription',
            'user_id'        => $user->id,
            'reference_type' => UserSubscription::class,
            'reference_id'   => $subscription->id,
            'currency_code'  => 'USD',
            'due_date'       => now()->toDateString(),
            'notes'          => "Renewal: {$plan->name} ({$subscription->billing_cycle})",
        ], [
            [
                'description' => "{$plan->name} Renewal",
                'details'     => "Billing cycle: " . ucfirst($subscription->billing_cycle),
                'quantity'    => 1,
                'unit_price'  => $plan->price,
                'type'        => 'service',
            ],
        ]);

        Log::info("Renewal invoice generated: #{$invoice->invoice_number} for user #{$user->id}");
        return $invoice;
    }

    /**
     * Process all due recurring subscriptions (called by scheduler).
     */
    public function processRenewals(): int
    {
        $count = 0;
        $renewals = UserSubscription::where('is_recurring', true)
            ->where('status', 'active')
            ->whereDate('expires_at', '<=', now()->addDays(1)) // expiring within 1 day
            ->with(['user.wallet', 'plan'])
            ->get();

        foreach ($renewals as $subscription) {
            try {
                $invoice = $this->generateRenewalInvoice($subscription);

                // Attempt auto-charge from wallet
                if ($subscription->user->wallet && $subscription->user->wallet->balance >= $invoice->total_amount) {
                    $this->invoiceService->applyWalletPayment($invoice);
                    $subscription->update([
                        'starts_at'  => now(),
                        'expires_at' => now()->addDays($subscription->plan->duration_days),
                    ]);
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::error("Renewal billing failed for subscription #{$subscription->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
