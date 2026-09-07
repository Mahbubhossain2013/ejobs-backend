<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdCampaignNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $type;
    public string $title;
    public string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $type, string $title, string $message)
    {
        $this->type = $type; // approval, rejection, low_balance, auto_pause, fraud_detected, ai_escalation
        $this->title = $title;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');

        return (new MailMessage)
            ->subject("[{$siteName} Ads] {$this->title}")
            ->greeting("Hello, {$notifiable->name}")
            ->line($this->message)
            ->action('View Ad Campaign Manager', url('/employer/campaigns'))
            ->line('Thank you for advertising with ' . $siteName . '!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
