<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Escrow;
use App\Models\Wallet;
use App\Models\Job;
use App\Models\User;
use App\Models\ProjectDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Notification\NotificationService;

class AutoReleaseEscrow extends Command
{
    protected $signature = 'escrow:auto-release';
    protected $description = 'Auto-release escrows that have been funded for more than 72 hours without dispute';

    public function handle()
    {
        $threshold = now()->subHours(72);

        // Find escrows funded before the threshold with no active dispute on the job
        $staleEscrows = Escrow::where('status', 'funded')
            ->where('created_at', '<=', $threshold)
            ->whereDoesntHave('job.disputes', function ($q) {
                $q->where('status', 'open');
            })
            ->get();

        $released = 0;

        foreach ($staleEscrows as $escrow) {
            try {
                DB::transaction(function () use ($escrow, &$released) {
                    $job = Job::find($escrow->job_id);
                    if (!$job || $job->project_status === 'disputed' || $job->project_status === 'cancelled') {
                        return;
                    }

                    // Read fee settings
                    $feePayer = \App\Models\Setting::where('key', 'escrow_fee_payer')->value('value') ?? 'candidate';
                    $feePercentSetting = \App\Models\Setting::where('key', 'escrow_fee_percent')->first();
                    $feePercent = $feePercentSetting ? (float) $feePercentSetting->value : 2.00;

                    // Calculate platform fee based on who pays
                    if ($feePayer === 'employer') {
                        $platformFee = 0;
                        $candidatePayout = $escrow->amount;
                    } else {
                        $platformFee = $escrow->amount * ($feePercent / 100.00);
                        $candidatePayout = $escrow->amount - $platformFee;
                    }

                    // Deduct from employer locked wallet BEFORE updating escrow
                    $employerWallet = Wallet::where('user_id', $escrow->employer_id)->lockForUpdate()->first();
                    if ($employerWallet) {
                        $originalPlatformFee = $escrow->platform_fee ?? 0;
                        $employerWallet->locked_balance = max(0, $employerWallet->locked_balance - ($escrow->amount + ($feePayer === 'employer' ? $originalPlatformFee : 0)));
                        $employerWallet->save();
                    }

                    // Update project states
                    $job->update(['project_status' => 'completed']);
                    $escrow->update(['status' => 'released', 'platform_fee' => $platformFee, 'released_at' => now()]);

                    // Credit candidate wallet
                    $candidateWallet = Wallet::firstOrCreate(['user_id' => $escrow->candidate_id]);
                    $candidateWallet->credit(
                        $candidatePayout,
                        'project_payout',
                        $escrow->id,
                        'Auto-released payment (72h): ' . ($job->title ?? 'Project')
                    );

                    // Credit admin wallet with platform commission (only if candidate pays fee)
                    if ($feePayer === 'candidate' && $platformFee > 0) {
                        $admin = User::role('admin')->first();
                        if ($admin) {
                            $adminWallet = Wallet::firstOrCreate(['user_id' => $admin->id]);
                            $adminWallet->credit(
                                $platformFee,
                                'project_commission',
                                $escrow->id,
                                'Auto-release commission: ' . ($job->title ?? 'Project')
                            );
                        }
                    }

                    $released++;
                });

                // Notify candidate
                $candidate = User::find($escrow->candidate_id);
                if ($candidate) {
                    app(NotificationService::class)->sendNotification(
                        $candidate,
                        'Escrow Auto-Released',
                        'Your escrow payment has been automatically released after 72 hours. Please check your wallet.',
                        'billing',
                        '/dashboard/wallet'
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Escrow Auto-Release Failed for ID: ' . $escrow->id, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Escrow auto-release complete. Released: {$released}");
        Log::info("Escrow auto-release complete. Released: {$released}");

        return Command::SUCCESS;
    }
}
