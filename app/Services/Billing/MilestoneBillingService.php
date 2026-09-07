<?php

namespace App\Services\Billing;

use App\Models\Escrow;
use App\Models\Invoice;

class MilestoneBillingService
{
    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Generate an escrow funding invoice when an employer funds escrow.
     */
    public function generateEscrowFundingInvoice(Escrow $escrow): Invoice
    {
        $escrow->loadMissing(['employer', 'job']);

        $invoice = $this->invoiceService->createInvoice([
            'type'            => 'escrow_funding',
            'user_id'         => $escrow->employer_id,
            'employer_id'     => $escrow->employer_id,
            'candidate_id'    => $escrow->candidate_id,
            'reference_type'  => Escrow::class,
            'reference_id'    => $escrow->id,
            'currency_code'   => 'USD',
            'platform_fee'    => $escrow->platform_fee ?? 0,
            'due_date'        => now()->toDateString(),
            'notes'           => "Escrow funded for job: " . ($escrow->job?->title ?? "Job #{$escrow->job_id}"),
        ], [
            [
                'description' => 'Escrow Funding',
                'details'     => 'Funds held in escrow for contract milestone',
                'quantity'    => 1,
                'unit_price'  => $escrow->amount,
                'type'        => 'service',
            ],
            ...($escrow->platform_fee > 0 ? [[
                'description' => 'Platform Service Fee',
                'details'     => 'Escrow management fee',
                'quantity'    => 1,
                'unit_price'  => $escrow->platform_fee,
                'type'        => 'fee',
            ]] : []),
        ]);

        // Link funding invoice to escrow
        $escrow->update(['funding_invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Generate an escrow release / milestone completion invoice.
     */
    public function generateEscrowReleaseInvoice(Escrow $escrow): Invoice
    {
        $escrow->loadMissing(['employer', 'candidate', 'job']);

        $invoice = $this->invoiceService->createInvoice([
            'type'            => 'escrow_release',
            'user_id'         => $escrow->employer_id,
            'employer_id'     => $escrow->employer_id,
            'candidate_id'    => $escrow->candidate_id,
            'reference_type'  => Escrow::class,
            'reference_id'    => $escrow->id,
            'currency_code'   => 'USD',
            'status'          => 'paid',
            'paid_at'         => now(),
            'due_date'        => now()->toDateString(),
            'notes'           => "Milestone completed for job: " . ($escrow->job?->title ?? "Job #{$escrow->job_id}"),
        ], [
            [
                'description' => 'Milestone Payment',
                'details'     => 'Escrow released to candidate upon milestone completion',
                'quantity'    => 1,
                'unit_price'  => $escrow->amount,
                'type'        => 'service',
            ],
        ]);

        // Link release invoice to escrow
        $escrow->update(['release_invoice_id' => $invoice->id]);

        // Credit candidate wallet
        $candidate = $escrow->candidate;
        if ($candidate && $candidate->wallet) {
            $candidate->wallet->credit(
                $escrow->amount,
                Invoice::class,
                $invoice->id,
                "Milestone payment for " . ($escrow->job?->title ?? "Job #{$escrow->job_id}")
            );
        }

        return $invoice;
    }

    /**
     * Generate a milestone invoice (standalone, not escrow-based).
     */
    public function generateMilestoneInvoice(int $employerId, int $candidateId, float $amount, string $description, array $meta = []): Invoice
    {
        return $this->invoiceService->createInvoice([
            'type'         => 'milestone',
            'user_id'      => $employerId,
            'employer_id'  => $employerId,
            'candidate_id' => $candidateId,
            'currency_code'=> 'USD',
            'due_date'     => now()->toDateString(),
            'notes'        => $description,
            'metadata'     => $meta,
        ], [
            [
                'description' => $description,
                'quantity'    => 1,
                'unit_price'  => $amount,
                'type'        => 'service',
            ],
        ]);
    }
}
