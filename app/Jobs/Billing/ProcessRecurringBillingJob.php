<?php

namespace App\Jobs\Billing;

use App\Services\Billing\InvoiceService;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * ProcessRecurringBillingJob
 *
 * Runs daily/weekly to:
 * 1. Process subscription renewals
 * 2. Mark overdue invoices
 * 3. Bill active boost campaigns
 */
class ProcessRecurringBillingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(
        InvoiceService             $invoiceService,
        SubscriptionBillingService $subscriptionService,
    ): void {
        Log::info('ProcessRecurringBillingJob started.');

        // 1. Mark overdue invoices
        $overdue = $invoiceService->markOverdueInvoices();
        Log::info("Billing: {$overdue} invoices marked overdue.");

        // 2. Process subscription renewals
        $renewals = $subscriptionService->processRenewals();
        Log::info("Billing: {$renewals} subscriptions renewed.");

        // 3. Send subscription expiring warning emails
        try {
            \Artisan::call('subscriptions:send-expiring-emails', ['--days' => 3]);
            Log::info("Billing: Subscription expiring emails sent.");
        } catch (\Throwable $e) {
            Log::error("Billing: Failed to send subscription expiring emails: " . $e->getMessage());
        }

        Log::info('ProcessRecurringBillingJob completed.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRecurringBillingJob failed: ' . $exception->getMessage());
    }
}
