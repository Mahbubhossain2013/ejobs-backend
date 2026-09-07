<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTransaction extends Model
{
    protected $fillable = [
        'invoice_id',
        'user_id',
        'type',
        'status',
        'amount',
        'currency_code',
        'payment_method',
        'payment_gateway',
        'gateway_transaction_id',
        'gateway_reference',
        'processed_at',
        'failure_reason',
        'gateway_response',
        'metadata',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'processed_at'     => 'datetime',
        'gateway_response' => 'array',
        'metadata'         => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }
}
