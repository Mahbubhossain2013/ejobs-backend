<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class EmailFallbackService
{
    /**
     * Resiliently send emails.
     * Attempts default SMTP, catches failures, and retries using configured secondary SMTP mailer.
     */
    public static function send($mailable, string $toEmail): bool
    {
        try {
            // Attempt standard configured mail delivery
            Mail::to($toEmail)->send($mailable);
            return true;
        } catch (\Exception $e) {
            Log::warning("Primary SMTP delivery failed: " . $e->getMessage() . ". Attempting secondary fallback mailer...");
            
            try {
                // Read secondary backup configuration dynamically
                $host = null;
                $port = null;
                $username = null;
                $password = null;

                if (class_exists(Setting::class) && Schema::hasTable('settings')) {
                    $host = Setting::where('key', 'secondary_mail_host')->value('value');
                    $port = Setting::where('key', 'secondary_mail_port')->value('value');
                    $username = Setting::where('key', 'secondary_mail_username')->value('value');
                    $password = Setting::where('key', 'secondary_mail_password')->value('value');
                }

                if ($host && $username) {
                    // Inject backup config dynamically into the mailer setup
                    config([
                        'mail.mailers.smtp.host' => $host,
                        'mail.mailers.smtp.port' => $port ?: 587,
                        'mail.mailers.smtp.username' => $username,
                        'mail.mailers.smtp.password' => $password,
                    ]);

                    // Resolve the mailer transport and retry
                    Mail::purge();
                    Mail::to($toEmail)->send($mailable);
                    
                    Log::info("Secondary backup SMTP successfully dispatched email to: {$toEmail}");
                    return true;
                }
                
                throw new \Exception("Secondary SMTP parameters not configured.");
            } catch (\Exception $ex) {
                Log::critical("All SMTP email transports failed! Exception: " . $ex->getMessage());
                
                // Fall back to queueing a retry in the database if queue is operational
                try {
                    Mail::to($toEmail)->queue($mailable);
                    Log::info("Email queued for database retry.");
                    return true;
                } catch (\Exception $queueEx) {
                    Log::error("Email queue backup failed: " . $queueEx->getMessage());
                }
            }
        }

        return false;
    }
}
