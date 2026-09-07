<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\Billing\ProcessRecurringBillingJob;
use App\Models\User;
use App\Models\Verification;
use App\Models\Setting;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;

// Existing schedules
Schedule::command('promotions:bill')->hourly();

Schedule::command('session:prune')->hourly();

Schedule::command('db:backup')->dailyAt('03:00');

// Process queued jobs on shared hosting cron (runs queue worker once per schedule tick).
// Paired with the web cron at /cron/run/{token} (see CronJobMonitor admin page).
// Emails, SMS, and notifications queued with the database driver are drained here.
Schedule::command('queue:work database --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping();

// Job Alerts: send matching notifications
Schedule::command('job-alerts:send')->dailyAt('09:00');

// 72-Hour Escrow Auto-Release: runs every hour to release stale escrows
Schedule::command('escrow:auto-release')->hourly();

// Billing: Process renewals and mark overdue invoices daily at midnight
Schedule::job(new ProcessRecurringBillingJob())->dailyAt('00:05');

// Automated Identity & Document Verification Reminders
Schedule::call(function () {
    // 1. Check if verification system is enabled
    $globalEnabled = Setting::where('key', 'verification_enabled')->value('value') ?? '1';
    if ($globalEnabled !== '1' && $globalEnabled !== 'true') {
        return;
    }

    $intervalHours = (int)(Setting::where('key', 'verification_reminder_interval_hours')->value('value') ?? '24');
    $maxCount = (int)(Setting::where('key', 'verification_reminder_max_count')->value('value') ?? '5');

    // 2. Query users who do NOT have the badge "verified"
    User::whereHas('roles', function($q) {
        $q->whereIn('name', ['candidate', 'employer']);
    })
    ->chunk(50, function ($users) use ($intervalHours, $maxCount) {
        foreach ($users as $user) {
            // Skip already fully verified users
            if ($user->hasBadge('verified')) {
                continue;
            }

            $role = $user->getRoleNames()->first() ?? 'candidate';
            $type = $role === 'employer' ? 'employer' : 'nid';

            // Find latest verification record of this type
            $latest = Verification::where('user_id', $user->id)
                ->where('verification_type', $type)
                ->first();

            // If a request is active (pending or approved), skip reminder
            if ($latest && in_array($latest->status, ['pending', 'manual_review', 'approved'])) {
                continue;
            }

            // Check reminder interval and ceiling count
            $reminderCount = $latest ? $latest->reminder_count : 0;
            $lastSent = $latest ? $latest->last_reminder_sent_at : null;

            if ($reminderCount >= $maxCount) {
                continue;
            }

            if ($lastSent && Carbon::parse($lastSent)->addHours($intervalHours)->isFuture()) {
                continue;
            }

            // Update reminder metadata state
            if ($latest) {
                $latest->update([
                    'reminder_count' => $reminderCount + 1,
                    'last_reminder_sent_at' => now(),
                ]);
            } else {
                Verification::create([
                    'user_id' => $user->id,
                    'verification_type' => $type,
                    'status' => 'failed', // Mark as failed/incomplete so it filters out of admin review queues
                    'notes' => 'Awaiting document upload. Automation reminder sent.',
                    'reminder_count' => 1,
                    'last_reminder_sent_at' => now(),
                ]);
            }

            // Dispatch warning notification
            $notificationService = app(NotificationService::class);
            if ($role === 'candidate') {
                $notificationService->sendNotification(
                    $user,
                    '🔒 Verify Your Identity details',
                    'Unlock full platform access, job applications, and security protection by verifying your NID details today!',
                    'warning',
                    '/dashboard/profile'
                );
            } else {
                $notificationService->sendNotification(
                    $user,
                    '🔒 Verify Company Trade License',
                    'Verify your company credentials and trade license today to activate job listings and wallet transactions.',
                    'warning',
                    '/employer/profile'
                );
            }
        }
    });
})->daily();