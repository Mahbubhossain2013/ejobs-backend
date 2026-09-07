<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_number',
        'user_id',
        'assigned_admin_id',
        'subject',
        'message',
        'category',
        'priority',
        'status',
    ];

    protected $appends = [
        'status_color',
        'priority_color',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                do {
                    $number = '#TIC-' . rand(100000, 999999);
                } while (self::where('ticket_number', $number)->exists());
                $ticket->ticket_number = $number;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'support_ticket_id')->oldest();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class, 'ticket_id')->oldest();
    }

    /**
     * Get style settings for status badges
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'danger',
            'pending' => 'warning',
            'in_progress' => 'info',
            'answered' => 'info',
            'resolved' => 'success',
            'closed' => 'gray',
            default => 'primary',
        };
    }

    /**
     * Get style settings for priority badges
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'primary',
            'low' => 'gray',
            default => 'primary',
        };
    }
}
