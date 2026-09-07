<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationUuid;

    public function __construct(Message $message, $conversationUuid = null)
    {
        // Eager load the sender so React gets the name immediately
        $this->message = $message->load('sender');
        $this->conversationUuid = $conversationUuid ?? $message->conversation?->uuid;
    }

    public function broadcastOn(): array
    {
        // Broadcast on a private channel specific to this conversation by UUID
        return [
            new PrivateChannel('conversation.' . $this->conversationUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Get the data to broadcast (full message with sender details).
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'email' => $this->message->sender->email,
                ],
                'message' => $this->message->message,
                'attachment_path' => $this->message->attachment_path,
                'created_at' => $this->message->created_at->toIso8601String(),
                'updated_at' => $this->message->updated_at->toIso8601String(),
            ],
            'conversationUuid' => $this->conversationUuid,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}