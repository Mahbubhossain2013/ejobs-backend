<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata'   => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Log an audit event.
     */
    public static function log(
        string $event,
        Model|null $model = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = []
    ): static {
        $request = request();

        return static::create([
            'user_id'        => auth()->id(),
            'event'          => $event,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->id,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'metadata'       => array_merge($metadata, [
                'request_id' => $request->header('X-Request-ID', uniqid()),
            ]),
        ]);
    }
}
