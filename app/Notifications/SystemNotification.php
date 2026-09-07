<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification
{
    use Queueable;

    protected array $data;

    /**
     * Create a new notification instance.
     * $data keys: title, message, action_url, type, channels
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Use admin-selected channels if provided
        if (!empty($this->data['channels'])) {
            return $this->data['channels'];
        }

        $channels = ['database'];

        $prefs = $notifiable->profile?->notification_settings ?? [];
        $type = $this->data['type'] ?? 'general';

        $emailEnabled = $prefs['email'][$type] ?? $prefs['email']['all'] ?? true;
        $realtimeEnabled = $prefs['realtime'][$type] ?? $prefs['realtime']['all'] ?? true;

        if ($emailEnabled) {
            $channels[] = 'mail';
        }

        if ($realtimeEnabled && $this->broadcastDriverConfigured()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Check whether a real broadcast driver (Reverb) is actually configured.
     * Avoids attempting a dead localhost connection on every notification.
     */
    protected function broadcastDriverConfigured(): bool
    {
        $driver = config('broadcasting.default');
        if ($driver === 'log' || $driver === 'null') {
            return false;
        }
        if ($driver === 'reverb') {
            return !empty(config('broadcasting.connections.reverb.app_id')) || !empty(env('REVERB_APP_ID'));
        }
        return !empty(config("broadcasting.connections.{$driver}.key"));
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Job Portal');
        return (new MailMessage)
            ->subject("[{$appName}] " . ($this->data['title'] ?? 'Notification Update'))
            ->greeting("Hello " . ($notifiable->name ?? '') . ",")
            ->line($this->data['message'] ?? '')
            ->action('View Details', url($this->data['action_url'] ?? '/dashboard'))
            ->line('Thank you for using our platform!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->data['title'] ?? 'System Announcement',
            'message' => $this->data['message'] ?? '',
            'action_url' => $this->data['action_url'] ?? '/dashboard',
            'type' => $this->data['type'] ?? 'general',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->data['title'] ?? 'System Announcement',
            'message' => $this->data['message'] ?? '',
            'action_url' => $this->data['action_url'] ?? '/dashboard',
            'type' => $this->data['type'] ?? 'general',
            'created_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Define the broadcast channels.
     */
    public function broadcastOn(): array
    {
        return ["private-users.{$this->notifiable->id}"];
    }
}
