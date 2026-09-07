<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Promotion;

class BoostBillingService
{
    public function __construct(protected InvoiceService $invoiceService) {}

    /**
     * Generate a job boost invoice when a promotion is created/approved.
     */
    public function generateBoostInvoice(Promotion $promotion): Invoice
    {
        $promotion->loadMissing(['user', 'job']);

        $type = match($promotion->type) {
            'featured_profile' => 'featured_profile',
            default            => 'job_boost',
        };

        $description = match($promotion->type) {
            'featured_profile' => 'Featured Profile Promotion',
            'job_boost'        => 'Job Boost – ' . ($promotion->job?->title ?? "Job #{$promotion->job_id}"),
            default            => ucfirst($promotion->type) . ' Promotion',
        };

        $amount = $promotion->total_budget ?? $promotion->daily_budget ?? 0;

        return $this->invoiceService->createInvoice([
            'type'           => $type,
            'user_id'        => $promotion->user_id,
            'employer_id'    => $promotion->user_id,
            'reference_type' => Promotion::class,
            'reference_id'   => $promotion->id,
            'currency_code'  => 'USD',
            'due_date'       => now()->toDateString(),
            'notes'          => "Promotion period: " . optional($promotion->start_date)->toDateString() . ' – ' . optional($promotion->end_date)->toDateString(),
        ], [
            [
                'description' => $description,
                'details'     => "Campaign type: " . ucfirst($promotion->type),
                'quantity'    => 1,
                'unit_price'  => $amount,
                'type'        => 'service',
            ],
        ]);
    }

    /**
     * Generate a daily boost billing invoice (for daily-budget promotions).
     */
    public function generateDailyBoostInvoice(Promotion $promotion, float $dailyCharge): Invoice
    {
        $promotion->loadMissing(['user', 'job']);

        return $this->invoiceService->createInvoice([
            'type'           => 'job_boost',
            'user_id'        => $promotion->user_id,
            'employer_id'    => $promotion->user_id,
            'reference_type' => Promotion::class,
            'reference_id'   => $promotion->id,
            'currency_code'  => 'USD',
            'status'         => 'paid',
            'paid_at'        => now(),
            'due_date'       => now()->toDateString(),
            'notes'          => "Daily boost charge for " . now()->toDateString(),
        ], [
            [
                'description' => 'Daily Job Boost – ' . ($promotion->job?->title ?? "Job #{$promotion->job_id}"),
                'details'     => "Impressions charged: " . ($promotion->impressions ?? 0),
                'quantity'    => 1,
                'unit_price'  => $dailyCharge,
                'type'        => 'service',
            ],
        ]);
    }

    /**
     * Process active promotions: charge daily budget from wallet and generate invoices.
     */
    public function processActiveCampaigns(): int
    {
        $count = 0;

        $promotions = Promotion::where('status', 'active')
            ->where('spent_amount', '<', \Illuminate\Support\Facades\DB::raw('total_budget'))
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with(['user.wallet', 'job'])
            ->get();

        foreach ($promotions as $promotion) {
            try {
                $charge = min($promotion->daily_budget, $promotion->total_budget - $promotion->spent_amount);
                if ($charge <= 0) continue;

                $wallet = $promotion->user?->wallet;
                if (!$wallet || $wallet->balance < $charge) continue;

                // Charge wallet
                $wallet->debit($charge, Promotion::class, $promotion->id, "Daily boost for: " . ($promotion->job?->title ?? "Promotion #{$promotion->id}"));

                // Update promotion
                $promotion->update([
                    'spent_amount'   => $promotion->spent_amount + $charge,
                    'last_billed_at' => now(),
                ]);

                // Generate daily invoice
                $this->generateDailyBoostInvoice($promotion, $charge);
                $count++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Boost billing failed for promotion #{$promotion->id}: " . $e->getMessage());
            }
        }

        return $count;
    }
}
