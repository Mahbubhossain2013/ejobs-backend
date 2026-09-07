<?php
use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

// Secure private channel: Only the logged-in user can listen to their own notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversation channel: Only employer or candidate in the conversation can listen
Broadcast::channel('conversation.{uuid}', function ($user, $uuid) {
    $conversation = Conversation::where('uuid', $uuid)->first();
    if (!$conversation) return false;
    return $conversation->employer_id === $user->id || $conversation->candidate_id === $user->id;
});