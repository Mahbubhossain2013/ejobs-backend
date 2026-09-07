<?php

namespace App\Jobs\Billing;

use App\Models\Invoice;
use App\Services\Billing\PdfGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string  $toEmail,
    ) {}

    public function handle(PdfGeneratorService $pdfService): void
    {
        $invoice = $this->invoice->fresh(['items', 'user', 'template']);

        // Ensure PDF exists
        if (!$invoice->pdf_path || !Storage::disk('public')->exists($invoice->pdf_path)) {
            $pdfService->generate($invoice);
        }

        $pdfPath = Storage::disk('public')->path($invoice->pdf_path);
        $subject  = "Invoice #{$invoice->invoice_number} from " . config('app.name');

        Mail::send(
            'emails.invoice_email',
            $pdfService->buildTemplateData($invoice),
            function ($message) use ($invoice, $pdfPath, $subject) {
                $message
                    ->to($this->toEmail)
                    ->subject($subject)
                    ->attach($pdfPath, [
                        'as'   => "invoice-{$invoice->invoice_number}.pdf",
                        'mime' => 'application/pdf',
                    ]);
            }
        );

        Log::info("Invoice email sent: #{$invoice->invoice_number} → {$this->toEmail}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Invoice email job failed for #{$this->invoice->invoice_number}: " . $exception->getMessage());
    }
}
