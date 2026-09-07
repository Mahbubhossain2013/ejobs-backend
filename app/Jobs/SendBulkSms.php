<?php

namespace App\Jobs;

use App\Models\BulkMessageRecipient;
use App\Models\Setting;
use App\Models\Wallet;
use App\Services\Notification\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public int $recipientId,
        public string $smsMessage
    ) {}

    public function handle(): void
    {
        $recipient = BulkMessageRecipient::with(['user.profile'])->find($this->recipientId);
        if (!$recipient) {
            return;
        }

        $batch = $recipient->batch;

        if ($recipient->status === 'sent') {
            return;
        }

        $candidate = $recipient->user;
        $phone = $recipient->recipient_phone ?: $candidate?->profile?->phone;
        $cleanPhone = app(SmsService::class)->cleanPhoneNumber((string) $phone);

        if (strlen($cleanPhone) !== 11 || !str_starts_with($cleanPhone, '01')) {
            $recipient->update(['status' => 'skipped', 'error' => 'Invalid phone number']);
            $batch?->refreshCounts();
            return;
        }

        $personalized = str_replace(
            ['{name}', '{job_title}', '{company}'],
            [
                $candidate?->name ?? 'there',
                $recipient->application?->job?->title ?? 'the position',
                $recipient->batch?->company?->name ?? '',
            ],
            $this->smsMessage
        );

        // Compute per-recipient SMS count (1 tk per segment, 160 chars each)
        $smsCount = (int) max(1, ceil(mb_strlen($personalized) / 160));
        $perSms = (float) (Setting::where('key', 'sms_charge_per_sms')->value('value') ?? 1);
        $cost = $smsCount * $perSms;

        $wallet = Wallet::where('user_id', $recipient->batch?->employer_id)->first();
        if (!$wallet || $wallet->balance < $cost) {
            $recipient->update(['status' => 'failed', 'error' => 'Insufficient wallet balance']);
            $batch?->refreshCounts();
            return;
        }

        $result = app(SmsService::class)->sendSms($cleanPhone, $personalized);

        if (($result['status'] ?? '') === 'success') {
            try {
                $wallet->debit($cost, 'sms_charge', $recipient->batch_id, "Bulk SMS ({$smsCount} SMS) to {$cleanPhone}");
                $recipient->update([
                    'status' => 'sent',
                    'error' => null,
                    'sms_count' => $smsCount,
                    'sms_cost' => $cost,
                    'attempts' => $recipient->attempts + 1,
                    'sent_at' => now(),
                ]);
                $batch?->refreshCounts();
            } catch (\Throwable $e) {
                Log::error("Bulk SMS wallet debit failed for recipient {$recipient->id}: " . $e->getMessage());
                $recipient->update(['status' => 'failed', 'error' => 'Wallet debit failed', 'attempts' => $recipient->attempts + 1]);
                $batch?->refreshCounts();
            }
        } else {
            $recipient->update([
                'status' => 'failed',
                'error' => $result['message'] ?? 'SMS gateway rejected',
                'attempts' => $recipient->attempts + 1,
            ]);
            $batch?->refreshCounts();
        }
    }
}