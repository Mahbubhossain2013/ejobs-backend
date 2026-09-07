<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingProfile extends Model
{
    protected $fillable = [
        'user_id',
        'billing_name',
        'company_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country_code',
        'vat_number',
        'tax_id',
        'currency_code',
        'is_business',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'is_business' => 'boolean',
        'is_default'  => 'boolean',
        'metadata'    => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted full address for invoice display.
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line_1,
            $this->address_line_2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country_code,
        ])->filter()->implode(', ');
    }

    /**
     * Get the billing name (company or personal).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?? $this->billing_name ?? $this->user?->name ?? '';
    }
}
