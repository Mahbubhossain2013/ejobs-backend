<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id', 
        'payout_gateway_id', 
        'amount', 
        'payment_method', 
        'charge', 
        'payable', 
        'account_details', 
        'status', 
        'admin_note'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payoutGateway()
    {
        return $this->belongsTo(PayoutGateway::class);
    }

    public function invoice()
    {
        return $this->morphOne(Invoice::class, 'reference');
    }
}