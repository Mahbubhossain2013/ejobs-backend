<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send a notification to a specific user
     */
    public function sendNotification(User $user, string $title, string $message, string $type = 'general', string $actionUrl = '/dashboard')
    {
        $payload = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
        ];

        try {
            $user->notify(new SystemNotification($payload));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Notification delivery error for User #{$user->id}: " . $e->getMessage());
            try {
                \Illuminate\Support\Facades\DB::table('notifications')->insert([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => SystemNotification::class,
                    'notifiable_type' => get_class($user),
                    'notifiable_id' => $user->id,
                    'data' => json_encode($payload),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $dbEx) {
                // Ignore fallback DB error
            }
        }
    }

    /**
     * Broadcast a system announcement to all active users
     */
    public function broadcastSystemAnnouncement(string $title, string $message, string $actionUrl = '/dashboard')
    {
        $payload = [
            'title' => $title,
            'message' => $message,
            'type' => 'announcement',
            'action_url' => $actionUrl,
        ];

        // Process in chunks to prevent memory overhead on massive databases
        User::chunk(100, function ($users) use ($payload) {
            Notification::send($users, new SystemNotification($payload));
        });
    }

    /**
     * Update notification preferences for a user
     */
    public function updatePreferences(User $user, array $prefs)
    {
        $profile = $user->profile;
        if (!$profile) {
            $profile = $user->profile()->create();
        }

        // Standard structure: 
        // [
        //   'email' => ['billing' => true, 'application' => true, 'activity' => true, 'general' => true],
        //   'realtime' => ['billing' => true, 'application' => true, 'activity' => true, 'general' => true]
        // ]
        $currentPrefs = $profile->notification_settings ?? [];
        
        $updatedPrefs = [
            'email' => array_merge($currentPrefs['email'] ?? [], $prefs['email'] ?? []),
            'realtime' => array_merge($currentPrefs['realtime'] ?? [], $prefs['realtime'] ?? []),
        ];

        $profile->update([
            'notification_settings' => $updatedPrefs
        ]);

        return $updatedPrefs;
    }
}
