<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkMessageRecipient extends Model
{
    protected $fillable = [
        'batch_id', 'user_id', 'application_id', 'channel', 'recipient_email',
        'recipient_phone', 'status', 'sms_count', 'sms_cost', 'error', 'attempts', 'sent_at',
    ];

    protected $casts = [
        'sms_cost' => 'decimal:2',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BulkMessageBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }
}