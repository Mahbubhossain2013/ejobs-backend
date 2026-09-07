<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'type',
        'user_id',
        'candidate_id',
        'employer_id',
        'reference_type',
        'reference_id',
        'parent_invoice_id',
        'invoice_template_id',
        'status',
        'currency_code',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'platform_fee_rate',
        'platform_fee',
        'total_amount',
        'amount_paid',
        'amount_due',
        'billing_name',
        'billing_email',
        'billing_company',
        'billing_address',
        'billing_vat_number',
        'billing_country',
        'payment_method',
        'payment_gateway',
        'payment_transaction_id',
        'paid_at',
        'due_date',
        'issued_at',
        'pdf_path',
        'pdf_generated_at',
        'sent_at',
        'viewed_at',
        'notes',
        'terms',
        'footer',
        'payment_instructions',
        'metadata',
    ];

    protected $casts = [
        'paid_at'           => 'datetime',
        'due_date'          => 'date',
        'issued_at'         => 'datetime',
        'pdf_generated_at'  => 'datetime',
        'sent_at'           => 'datetime',
        'viewed_at'         => 'datetime',
        'subtotal'          => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'tax_rate'          => 'decimal:4',
        'tax_amount'        => 'decimal:2',
        'platform_fee_rate' => 'decimal:4',
        'platform_fee'      => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'amount_paid'       => 'decimal:2',
        'amount_due'        => 'decimal:2',
        'metadata'          => 'array',
    ];

    // ==================== Relationships ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InvoiceTemplate::class, 'invoice_template_id');
    }

    public function parentInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_invoice_id');
    }

    public function refundInvoices(): HasMany
    {
        return $this->hasMany(self::class, 'parent_invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InvoiceTransaction::class)->latest();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class)->latest('occurred_at');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** Polymorphic reference (subscription, deposit, promotion, escrow…) */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    // ==================== Scopes ====================

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                     ->whereDate('due_date', '<', now());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ==================== Accessors / Helpers ====================

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending'
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    public function formattedTotal(): string
    {
        return number_format($this->total_amount, 2) . ' ' . $this->currency_code;
    }

    public function statusBadge(): array
    {
        return match($this->status) {
            'paid'          => ['label' => 'Paid',          'color' => 'success'],
            'pending'       => ['label' => 'Pending',       'color' => 'warning'],
            'overdue'       => ['label' => 'Overdue',       'color' => 'danger'],
            'partially_paid'=> ['label' => 'Partial',       'color' => 'info'],
            'refunded'      => ['label' => 'Refunded',      'color' => 'gray'],
            'cancelled'     => ['label' => 'Cancelled',     'color' => 'gray'],
            'void'          => ['label' => 'Void',          'color' => 'gray'],
            'draft'         => ['label' => 'Draft',         'color' => 'secondary'],
            default         => ['label' => ucfirst($this->status), 'color' => 'gray'],
        };
    }

    public function typeLabel(): string
    {
        return match($this->type) {
            'subscription'     => 'Subscription',
            'job_boost'        => 'Job Boost',
            'featured_profile' => 'Featured Profile',
            'wallet_deposit'   => 'Wallet Deposit',
            'wallet_withdrawal'=> 'Wallet Withdrawal',
            'milestone'        => 'Milestone Payment',
            'contract_payment' => 'Contract Payment',
            'service_fee'      => 'Service Fee',
            'escrow_funding'   => 'Escrow Funding',
            'escrow_release'   => 'Escrow Release',
            'refund'           => 'Refund',
            'manual'           => 'Manual Invoice',
            default            => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    // ==================== Static Helpers ====================

    /**
     * Generate a unique, type-prefixed invoice number.
     * Format: INV-SUB-2026-000001
     */
    public static function generateInvoiceNumber(string $type = 'manual'): string
    {
        $prefix = match($type) {
            'subscription'     => 'SUB',
            'job_boost'        => 'BOOST',
            'featured_profile' => 'FEAT',
            'wallet_deposit'   => 'DEP',
            'wallet_withdrawal'=> 'WDR',
            'milestone'        => 'MLS',
            'contract_payment' => 'CTR',
            'service_fee'      => 'SVC',
            'escrow_funding'   => 'ESC',
            'escrow_release'   => 'REL',
            'refund'           => 'RFN',
            'manual'           => 'MAN',
            default            => 'INV',
        };

        $year = now()->year;

        // Use DB::transaction with lockForUpdate to prevent race conditions
        return \Illuminate\Support\Facades\DB::transaction(function () use ($prefix, $year) {
            $last = self::withTrashed()
                ->where('invoice_number', 'like', "INV-{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->count();

            $sequence = str_pad($last + 1, 6, '0', STR_PAD_LEFT);

            return "INV-{$prefix}-{$year}-{$sequence}";
        });
    }
}
