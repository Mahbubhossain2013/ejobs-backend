<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\TaxSetting;

class TaxCalculationService
{
    /**
     * Calculate tax for a given subtotal.
     *
     * @return array{rate: float, amount: float, label: string, is_inclusive: bool}
     */
    public function calculate(float $subtotal, string $countryCode = '', string $type = 'all'): array
    {
        $rate = TaxSetting::getRateFor($countryCode, $type);
        $setting = $this->getSetting($countryCode, $type);

        if ($rate <= 0) {
            return [
                'rate'         => 0.0,
                'amount'       => 0.0,
                'label'        => 'Tax',
                'is_inclusive' => false,
            ];
        }

        $isInclusive = $setting?->is_inclusive ?? false;
        $amount = $isInclusive
            ? round($subtotal - ($subtotal / (1 + $rate / 100)), 2)
            : round($subtotal * ($rate / 100), 2);

        return [
            'rate'         => $rate,
            'amount'       => $amount,
            'label'        => $setting?->label ?? 'VAT',
            'is_inclusive' => $isInclusive,
        ];
    }

    /**
     * Calculate tax on a full invoice (sums from items if available).
     */
    public function calculateForInvoice(Invoice $invoice): array
    {
        $country = $invoice->billing_country ?? '';
        return $this->calculate((float) $invoice->subtotal, $country, $invoice->type);
    }

    private function getSetting(string $countryCode, string $type): ?\App\Models\TaxSetting
    {
        $setting = \App\Models\TaxSetting::active()
            ->where('country_code', $countryCode)
            ->where(fn($q) => $q->where('applies_to', $type)->orWhere('applies_to', 'all'))
            ->orderByDesc('is_default')
            ->first();

        if (!$setting && $countryCode !== '') {
            $setting = \App\Models\TaxSetting::active()
                ->whereNull('country_code')
                ->where(fn($q) => $q->where('applies_to', $type)->orWhere('applies_to', 'all'))
                ->orderByDesc('is_default')
                ->first();
        }

        return $setting;
    }
}
