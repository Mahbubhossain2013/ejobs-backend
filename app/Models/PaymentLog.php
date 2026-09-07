<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentLog — Immutable audit trail for all billing events.
 * Records are never updated; only appended.
 */
class PaymentLog extends Model
{
    // Disable automatic updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'event',
        'actor_type',
        'actor_id',
        'description',
        'amount',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'old_values'  => 'array',
        'new_values'  => 'array',
        'metadata'    => 'array',
        'amount'      => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Quick factory method to record a billing event.
     */
    public static function record(
        ?Invoice $invoice,
        string $event,
        string $description = '',
        array $meta = [],
        ?float $amount = null,
        string $actorType = 'system',
        ?int $actorId = null,
    ): self {
        return self::create([
            'invoice_id'  => $invoice?->id,
            'user_id'     => $invoice?->user_id,
            'event'       => $event,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'description' => $description,
            'amount'      => $amount,
            'metadata'    => $meta,
            'occurred_at' => now(),
        ]);
    }
}
