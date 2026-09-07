<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'locked_balance', 'withdrawable_balance', 'is_frozen', 'freeze_reason'];

    protected $casts = [
        'balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
        'withdrawable_balance' => 'decimal:2',
        'is_frozen' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    /**
     * Used by Admin Panel and Gateways to safely ADD money
     */
    public function credit(float $amount, string $refType, ?int $refId, string $description)
    {
        DB::transaction(function () use ($amount, $refType, $refId, $description) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();
            $wallet->balance += $amount;
            $wallet->withdrawable_balance += $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $amount,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => $description,
                'status' => 'completed'
            ]);

            // Invalidate cached wallet balance after mutation
            Cache::forget("wallet_balance_{$wallet->user_id}");
        });
    }

    /**
     * Used by Admin Panel to safely DEDUCT money
     */
    public function debit(float $amount, string $refType, ?int $refId, string $description)
    {
        DB::transaction(function () use ($amount, $refType, $refId, $description) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();
            
            if ($wallet->balance < $amount) {
                throw new Exception("Employer does not have enough balance.");
            }

            $wallet->balance = max(0, $wallet->balance - $amount);
            $wallet->withdrawable_balance = max(0, $wallet->withdrawable_balance - $amount);
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'description' => $description,
                'status' => 'completed'
            ]);

            // Invalidate cached wallet balance after mutation
            Cache::forget("wallet_balance_{$wallet->user_id}");
        });
    }
}