<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'name',
        'label',
        'rate',
        'country_code',
        'region',
        'is_inclusive',
        'is_active',
        'is_default',
        'applies_to',
        'description',
    ];

    protected $casts = [
        'rate'         => 'decimal:4',
        'is_inclusive' => 'boolean',
        'is_active'    => 'boolean',
        'is_default'   => 'boolean',
    ];

    /**
     * Scope: only active tax rules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the applicable tax rate for a given country.
     * Priority: exact country match → global (null country) → 0
     */
    public static function getRateFor(string $countryCode = '', string $type = 'all'): float
    {
        // Try country + type match
        $setting = self::active()
            ->where('country_code', $countryCode)
            ->where(fn($q) => $q->where('applies_to', $type)->orWhere('applies_to', 'all'))
            ->orderByDesc('is_default')
            ->first();

        if (!$setting) {
            // Fallback to global (null country)
            $setting = self::active()
                ->whereNull('country_code')
                ->where(fn($q) => $q->where('applies_to', $type)->orWhere('applies_to', 'all'))
                ->orderByDesc('is_default')
                ->first();
        }

        return $setting ? (float) $setting->rate : 0.0;
    }

    /**
     * Set this as the global default, clearing others.
     */
    public function setAsDefault(): void
    {
        self::whereNull('country_code')->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
