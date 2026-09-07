<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class PdfGeneratorService
{
    /**
     * Generate PDF for an invoice and store it.
     * Returns the storage path of the generated PDF.
     */
    public function generate(Invoice $invoice): string
    {
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        $data = $this->buildTemplateData($invoice, $template);

        // Use Dompdf (built into Laravel) for PDF rendering
        $html = View::make('invoices.templates.' . $this->resolveView($template), $data)->render();

        $pdf = $this->renderWithDomPdf($html);

        $path = 'invoices/pdfs/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($path, $pdf);

        // Update the invoice record
        $invoice->update([
            'pdf_path'          => $path,
            'pdf_generated_at'  => now(),
        ]);

        return $path;
    }

    /**
     * Get the raw HTML preview of an invoice (for browser rendering).
     */
    public function getHtml(Invoice $invoice): string
    {
        $template = $invoice->template ?? InvoiceTemplate::getDefault();
        $data = $this->buildTemplateData($invoice, $template);
        return View::make('invoices.templates.' . $this->resolveView($template), $data)->render();
    }

    /**
     * Get the public URL for the invoice PDF.
     */
    public function getPdfUrl(Invoice $invoice): ?string
    {
        if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
            return Storage::disk('public')->url($invoice->pdf_path);
        }
        return null;
    }

    /**
     * Build all data needed by the invoice Blade template.
     */
    public function buildTemplateData(Invoice $invoice, ?InvoiceTemplate $template = null): array
    {
        $template = $template ?? InvoiceTemplate::getDefault();
        $invoice->loadMissing(['items', 'user', 'template']);

        return [
            'invoice'  => $invoice,
            'items'    => $invoice->items,
            'user'     => $invoice->user,
            'template' => $template,
            // Computed display values
            'subtotal_formatted'  => number_format($invoice->subtotal, 2),
            'tax_formatted'       => number_format($invoice->tax_amount, 2),
            'discount_formatted'  => number_format($invoice->discount_amount, 2),
            'total_formatted'     => number_format($invoice->total_amount, 2),
            'currency'            => $invoice->currency_code,
            'status_label'        => $invoice->statusBadge()['label'],
            'type_label'          => $invoice->typeLabel(),
            'logo_url'            => $template?->logo_url,
            'primary_color'       => $template?->primary_color ?? '#1a56db',
            'secondary_color'     => $template?->secondary_color ?? '#e1effe',
            'company_name'        => $template?->company_name ?? config('app.name'),
            'company_email'       => $template?->company_email,
            'company_phone'       => $template?->company_phone,
            'company_address'     => $template?->company_address,
            'footer_text'         => $template?->footer_text ?? '',
            'payment_instructions'=> $template?->payment_instructions ?? '',
            'terms'               => $invoice->terms ?? $template?->terms_and_conditions ?? '',
            'tax_label'           => $template?->tax_label ?? 'VAT',
            'watermark_text'      => $template?->watermark_text,
            'custom_css'          => $template?->custom_css ?? '',
            'currency_symbol'     => $template?->currency_symbol ?? '$',
        ];
    }

    private function resolveView(?InvoiceTemplate $template): string
    {
        if ($template && $template->template_view) {
            // template_view is like "invoices.templates.default"
            // We only need the last segment for the sub-view name
            $parts = explode('.', $template->template_view);
            return end($parts); // e.g. "default"
        }
        return 'default';
    }

    private function renderWithDomPdf(string $html): string
    {
        // Use barryvdh/laravel-dompdf if installed, otherwise fall back to pure PHP dompdf
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4')
                ->output();
        }

        // Fallback: Use dompdf directly
        $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}
