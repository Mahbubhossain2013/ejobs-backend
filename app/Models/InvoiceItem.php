<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'details',
        'quantity',
        'unit',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'total',
        'type',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'quantity'         => 'decimal:2',
        'unit_price'       => 'decimal:2',
        'discount_percent' => 'decimal:4',
        'discount_amount'  => 'decimal:2',
        'tax_rate'         => 'decimal:4',
        'tax_amount'       => 'decimal:2',
        'subtotal'         => 'decimal:2',
        'total'            => 'decimal:2',
        'metadata'         => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Compute and set subtotal + total from current values.
     */
    public function computeTotals(): void
    {
        $lineSubtotal = $this->quantity * $this->unit_price;
        $discount = $this->discount_amount > 0
            ? $this->discount_amount
            : ($lineSubtotal * ($this->discount_percent / 100));

        $this->subtotal = $lineSubtotal - $discount;
        $this->tax_amount = $this->subtotal * ($this->tax_rate / 100);
        $this->total = $this->subtotal + $this->tax_amount;
    }
}
