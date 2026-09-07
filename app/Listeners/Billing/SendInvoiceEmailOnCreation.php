<?php

namespace App\Listeners\Billing;

use App\Events\Billing\InvoiceCreated;
use App\Jobs\Billing\SendInvoiceEmailJob;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoiceEmailOnCreation implements ShouldQueue
{
    public string $queue = 'billing';

    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        // Only send if email delivery is enabled globally
        $autoSend = true; // Default: on
        try {
            $setting = Setting::where('key', 'invoice_auto_email')->first();
            $autoSend = $setting ? (bool) $setting->value : true;
        } catch (\Throwable) {}

        if (!$autoSend) return;

        // Don't auto-email refund or void invoices
        if (in_array($invoice->status, ['void', 'cancelled'])) return;

        $email = $invoice->billing_email ?? $invoice->user?->email;
        if (!$email) return;

        SendInvoiceEmailJob::dispatch($invoice, $email)->onQueue('billing');
    }
}
