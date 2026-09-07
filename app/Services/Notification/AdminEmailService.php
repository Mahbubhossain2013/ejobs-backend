<?php

namespace App\Services\Notification;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminEmailService
{
    public static function notifyAdmins(string $subject, string $view, array $data): void
    {
        try {
            $adminEmails = User::role('admin')->pluck('email')->filter()->values()->all();
            if (empty($adminEmails)) return;

            $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
            $data['brandName'] = $brandName;

            foreach ($adminEmails as $email) {
                Mail::send($view, $data, function ($mail) use ($email, $subject, $brandName) {
                    $mail->to($email)
                         ->subject("{$brandName} — {$subject}");
                });
            }
        } catch (\Throwable $e) {
            Log::error("Admin notification email failed: " . $e->getMessage());
        }
    }

    public static function notifyUser(User $user, string $subject, string $view, array $data): void
    {
        try {
            if (!$user || !$user->email) return;

            $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
            $data['brandName'] = $brandName;

            Mail::send($view, $data, function ($mail) use ($user, $subject, $brandName) {
                $mail->to($user->email)
                     ->subject("{$brandName} — {$subject}");
            });
        } catch (\Throwable $e) {
            Log::error("User notification email failed for user {$user->id}: " . $e->getMessage());
        }
    }
}
