<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdminNotificationLog extends Model {
    protected $fillable = [
        'title', 
        'message', 
        'target_audience', 
        'sent_count', 
        'status', 
        'channels', 
        'action_url'
    ];

    protected $casts = [
        'channels' => 'array',
    ];
}