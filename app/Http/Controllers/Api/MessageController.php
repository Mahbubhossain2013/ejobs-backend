<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Setting;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    /**
     * Get all active conversations for the logged-in user's Inbox.
     */
    public function getConversations()
    {
        try {
            $user = Auth::user();

            // Query conversations where user is employer or candidate
            $conversations = Conversation::where(function($query) use ($user) {
                    $query->where('employer_id', $user->id)
                          ->orWhere('candidate_id', $user->id);
                })
                ->with([
                    'job:id,title',
                    'messages' => fn($q) => $q->latest()->limit(1),
                    'employer:id,name,username,avatar',
                    'candidate:id,name,username,avatar'
                ])
                ->get();

            // Skip deleted conversations
            $conversations = $conversations->filter(function ($conv) use ($user) {
                $deletedBy = $conv->deleted_by ?? [];
                return !in_array($user->id, $deletedBy);
            });

            // Batch-load unread counts in ONE query (fix N+1)
            $convIds = $conversations->pluck('id');
            $unreadCounts = Message::whereIn('conversation_id', $convIds)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->select('conversation_id', \DB::raw('count(*) as unread'))
                ->groupBy('conversation_id')
                ->pluck('unread', 'conversation_id');

            $conversations = $conversations->map(function ($conv) use ($user, $unreadCounts) {
                // Determine who the "other party" is
                if ($user->id === $conv->employer_id) {
                    $conv->other_party = $conv->candidate;
                } else {
                    $conv->other_party = $conv->employer;
                }

                $conv->unread_count = $unreadCounts->get($conv->id, 0);

                $mutedBy = $conv->muted_by ?? [];
                $conv->is_muted = in_array($user->id, $mutedBy);

                return $conv;
            })
            ->sortByDesc(fn ($conv) => $conv->messages->first()?->created_at ?? $conv->created_at)
            ->values();

            return response()->json(['status' => true, 'data' => $conversations]);
        } catch (\Throwable $e) {
            Log::error('Get Conversations Error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Error fetching conversations'], 500);
        }
    }
    

    /**
     * Get or Create a PERMANENT direct conversation link.
     */
    public function fetchDirectMessages($targetUserId)
    {
        try {
            $user = Auth::user();
            $targetUser = User::findOrFail($targetUserId);

            if ($user->id == $targetUserId) {
                return response()->json(['status' => false, 'message' => 'Cannot message yourself'], 400);
            }

            // This ensures the query is ALWAYS the same, no matter who initiates the chat
            $employer = $user->hasRole('employer') ? $user : $targetUser;
            $candidate = $user->hasRole('candidate') ? $user : $targetUser;

            $conversation = Conversation::firstOrCreate(
                [
                    'employer_id' => $employer->id,
                    'candidate_id' => $candidate->id,
                    'job_id' => null
                ],
                [
                    'uuid' => bin2hex(random_bytes(8))
                ]
            );

            // Mark unread messages as read (do this FIRST, before loading)
            $conversation->messages()
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            // Load only last 50 messages (no need to load entire history on connect)
            $messages = $conversation->messages()
                ->with('sender:id,name,username')
                ->latest()
                ->limit(50)
                ->get()
                ->reverse()
                ->values();

            return response()->json([
                'status' => true,
                'data' => ['messages' => $messages],
                'conversation' => $conversation,
            ]);

        } catch (\Throwable $e) {
            Log::error('Fetch DM Error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(), ['user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Error fetching messages'], 500);
        }
    }

    /**
     * Send a direct message to a target user (by user ID).
     * Creates or finds the conversation automatically.
     */
    public function sendDirectMessage(Request $request, $targetUserId)
    {
        try {
            $request->validate([
                'message' => 'nullable|string|max:5000',
                'attachment' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,zip,rar,7z,txt,js,ts,jsx,tsx,py,java,php,html,css,json,xml',
            ]);

            // Must have at least message text or an attachment
            if (empty($request->input('message')) && !$request->hasFile('attachment')) {
                return response()->json(['status' => false, 'message' => 'Message or attachment required'], 422);
            }

            $user = Auth::user();
            $targetUser = User::findOrFail($targetUserId);

            if ($user->id == $targetUserId) {
                return response()->json(['status' => false, 'message' => 'Cannot message yourself'], 400);
            }

            // Safety check
            $trustScore = \App\Models\UserTrustScore::where('user_id', $user->id)->first();
            if ($trustScore && $trustScore->trust_score < 40) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your messaging privilege has been temporarily suspended.'
                ], 403);
            }

            // Find or create conversation (employer/candidate ordering)
            $employer = $user->hasRole('employer') ? $user : $targetUser;
            $candidate = $user->hasRole('candidate') ? $user : $targetUser;

            $conversation = Conversation::firstOrCreate(
                [
                    'employer_id' => $employer->id,
                    'candidate_id' => $candidate->id,
                    'job_id' => null,
                ],
                [
                    'uuid' => bin2hex(random_bytes(8)),
                ]
            );

            // AI moderation scan
            $messageText = $request->input('message', '');
            if ($messageText) {
                $scanResult = $this->scanForBypassingViolations($user, $messageText);
                if ($scanResult['blocked']) {
                    return response()->json([
                        'status' => false,
                        'message' => $scanResult['explanation'],
                        'violation' => true,
                    ], 403);
                }
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                // Handle both single file and multiple files (FormData with same key)
                $files = $request->file('attachment');
                $file = is_array($files) ? reset($files) : $files;
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $mimeType = $file->getClientMimeType();
                    if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/webp' && in_array($mimeType, ['image/jpeg', 'image/png'])) {
                        $webp = $this->convertToWebp($file);
                        $attachmentPath = $webp ?? $file->store('message-attachments', 'public');
                    } else {
                        $attachmentPath = $file->store('message-attachments', 'public');
                    }
                }
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $messageText,
                'attachment_path' => $attachmentPath,
            ]);

            try {
                broadcast(new MessageSent($message, $conversation->uuid))->toOthers();
            } catch (\Throwable $e) {
                Log::error("Broadcast failed: " . $e->getMessage());
            }

            // Send email notification to recipient
            try {
                $recipientId = $conversation->employer_id === $user->id
                    ? $conversation->candidate_id
                    : $conversation->employer_id;
                $recipient = User::find($recipientId);
                if ($recipient && $recipient->email) {
                    $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                    $messagesUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/messages';
                    $preview = mb_strimwidth(strip_tags($messageText), 0, 100, '...');
                    Mail::to($recipient->email)->queue(new \App\Mail\GenericMail('emails.new_message_notification', [
                        'brandName' => $brandName,
                        'recipientName' => $recipient->name,
                        'senderName' => $user->name,
                        'preview' => $preview,
                        'messagesUrl' => $messagesUrl,
                    ], "{$brandName} — New message from {$user->name}"));
                }
            } catch (\Throwable $e) {
                Log::error("Message notification email failed: " . $e->getMessage());
            }

            return response()->json([
                'status' => true,
                'data' => $message->load('sender:id,name,username'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Send Direct Message Error: ' . $e->getMessage(), ['user_id' => Auth::id(), 'target' => $targetUserId]);
            return response()->json(['status' => false, 'message' => 'Error sending message'], 500);
        }
    }

    /**
     * Fetch all messages for a specific conversation by its UUID.
     */
    public function fetchMessagesByUuid($uuid)
    {
        try {
            $user = Auth::user();
            
            // Log the request for debugging
            Log::info('Fetching messages for UUID: ' . $uuid . ' by user: ' . $user->id);
            
            $conversation = Conversation::where('uuid', $uuid)->first();
            
            if (!$conversation) {
                Log::warning('Conversation not found for UUID: ' . $uuid);
                return response()->json(['status' => false, 'message' => "Conversation not found"], 404);
            }

            // Verify user is a participant in this conversation
            if ($conversation->employer_id !== $user->id && $conversation->candidate_id !== $user->id) {
                Log::warning('Unauthorized access attempt. User: ' . $user->id . ', Employer: ' . $conversation->employer_id . ', Candidate: ' . $conversation->candidate_id);
                return response()->json(['status' => false, 'message' => "Unauthorized"], 403);
            }

            // Mark unread messages as read
            $conversation->messages()->where('sender_id', '!=', $user->id)->where('is_read', false)->update(['is_read' => true]);

            // Correctly fetch the "other person" details for the chat window header
            if ($user->id === $conversation->employer_id) {
                $conversation->other_party = User::select('id', 'name', 'username')->find($conversation->candidate_id);
            } else {
                $conversation->other_party = User::select('id', 'name', 'username')->find($conversation->employer_id);
            }

            $messages = $conversation->messages()->with('sender:id,name,username')->oldest()->get();

            return response()->json(['status' => true, 'data' => $messages, 'conversation' => $conversation]);
        } catch (\Throwable $e) {
            Log::error('Fetch Messages Error: ' . $e->getMessage(), ['uuid' => $uuid, 'user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Error loading messages'], 500);
        }
    }
    
    /**
     * Send a message to a conversation via its UUID.
     */
    /**
     * Send a message to a conversation via its UUID.
     */
    public function sendMessageByUuid(Request $request, $uuid)
    {
        try {
            $request->validate([
                'message' => 'nullable|string|max:5000',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt,zip|max:20480',
            ]);

            $user = Auth::user();
            
            // Phase 3: Check for Safety Suspensions
            $trustScore = \App\Models\UserTrustScore::where('user_id', $user->id)->first();
            if ($trustScore && $trustScore->trust_score < 40) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your workspace messaging privilege has been temporarily suspended due to multiple safety violations. Please contact support.'
                ], 403);
            }

            Log::info('Sending message to conversation UUID: ' . $uuid . ' by user: ' . $user->id);
            
            $conversation = Conversation::where('uuid', $uuid)->first();
            
            if (!$conversation) {
                Log::warning('Conversation not found for UUID: ' . $uuid);
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            // Verify user is a participant in this conversation
            if ($conversation->employer_id !== $user->id && $conversation->candidate_id !== $user->id) {
                Log::warning('Unauthorized send attempt. User: ' . $user->id . ', Employer: ' . $conversation->employer_id . ', Candidate: ' . $conversation->candidate_id);
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // Phase 3: Intercept and Moderated Message with AI Scanning Layer
            if ($request->filled('message')) {
                $scanResult = $this->scanForBypassingViolations($user, $request->message);
                if ($scanResult['blocked']) {
                    return response()->json([
                        'status' => false,
                        'message' => $scanResult['explanation'],
                        'violation' => true
                    ], 403); // Forbidden bypass block!
                }
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                // Handle both single file and multiple files (FormData with same key)
                $files = $request->file('attachment');
                $file = is_array($files) ? reset($files) : $files;
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $mimeType = $file->getClientMimeType();
                    $isImage = str_starts_with($mimeType, 'image/');

                    if ($isImage && $mimeType !== 'image/webp' && in_array($mimeType, ['image/jpeg', 'image/png'])) {
                        $webp = $this->convertToWebp($file);
                        $attachmentPath = $webp ?? $file->store('chat_attachments', 'public');
                    } else {
                        $attachmentPath = $file->store('chat_attachments', 'public');
                    }
                }
            }

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'message' => $request->message,
                'attachment_path' => $attachmentPath
            ]);

            try {
                broadcast(new MessageSent($message, $conversation->uuid))->toOthers();
            } catch (\Throwable $e) {
                Log::error("Broadcast failed: " . $e->getMessage());
            }

            // Send email notification to recipient
            try {
                $recipientId = $conversation->employer_id === $user->id
                    ? $conversation->candidate_id
                    : $conversation->employer_id;
                $recipient = User::find($recipientId);
                if ($recipient && $recipient->email) {
                    $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');
                    $messagesUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/messages';
                    $preview = mb_strimwidth(strip_tags($request->message ?? ''), 0, 100, '...');
                    Mail::to($recipient->email)->queue(new \App\Mail\GenericMail('emails.new_message_notification', [
                        'brandName' => $brandName,
                        'recipientName' => $recipient->name,
                        'senderName' => $user->name,
                        'preview' => $preview,
                        'messagesUrl' => $messagesUrl,
                    ], "{$brandName} — New message from {$user->name}"));
                }
            } catch (\Throwable $e) {
                Log::error("Message notification email failed: " . $e->getMessage());
            }

            return response()->json(['status' => true, 'data' => $message->load('sender:id,name,username')]);
            
        } catch (\Throwable $e) {
            Log::error('Send Message Error: ' . $e->getMessage(), ['uuid' => $uuid, 'user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Error sending message'], 500);
        }
    }

    /**
     * Phase 3: Scan messages for phone numbers, WhatsApp, Telegram, email, and social usernames using static Regex & Failover AI provider
     */
    protected function scanForBypassingViolations(User $user, string $text): array
    {
        $violations = [];
        
        // Static Check 1: Email Address
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) {
            $violations[] = 'email';
        }
        
        // Static Check 2: BD or Global Phone Number
        if (preg_match('/(?:\+88|88)?(?:01[3-9]\d{8})/', $text) || preg_match('/(\+?\d[\s\-\(\)]*){9,}/', $text)) {
            $violations[] = 'phone';
        }

        // Static Check 3: Social & Chat handles
        $keywords = ['whatsapp', 'telegram', 'wa.me', 't.me', 'discord', 'skype', 'bkash', 'nagad', 'rocket', 'paypal', 'pay outside'];
        $lowerText = strtolower($text);
        foreach ($keywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                $violations[] = 'external_contact_or_payment';
            }
        }

        // Contextual Check 4: Deep AI Scanning using failover-safe AiManagerService
        $prompt = "You are a professional security and contact information bypassing AI scanner.
        Scan the following text submitted by a candidate or employer on our freelance platform:
        Text: \"{$text}\"
        
        Business Rule: Users are strictly forbidden from sharing emails, phone numbers, WhatsApp, Telegram, Discord, Skype, social handles, external payment details, or meeting links.
        
        Is there any violation of our business rule in this text?
        Return ONLY a raw JSON object (no markdown, no preamble) with these exact keys:
        {
            \"violation_detected\": true/false,
            \"violations_found\": [\"email\", \"phone\", \"whatsapp\", \"payment\", \"discord\", \"external_link\"],
            \"confidence_score\": 0.95,
            \"explanation\": \"Brief reason why it is blocked\"
        }";

        try {
            $aiResponse = \App\Services\Ai\AiManagerService::ask($prompt, 0.1, 500);
            $cleanJson = trim(str_replace(['```json', '```'], '', $aiResponse));
            $result = json_decode($cleanJson, true);

            if ($result && isset($result['violation_detected']) && $result['violation_detected']) {
                $violations = array_unique(array_merge($violations, $result['violations_found'] ?? []));
                $confidence = $result['confidence_score'] ?? 0.90;
                $explanation = $result['explanation'] ?? 'AI flags suspicious external contacts.';
            }
        } catch (\Exception $e) {
            Log::warning("AI message scan failover triggered: " . $e->getMessage());
        }

        if (count($violations) > 0) {
            // Log the violation
            \App\Models\AiModerationLog::create([
                'user_id' => $user->id,
                'message_text' => $text,
                'detected_violations' => $violations,
                'confidence_score' => $confidence ?? 0.95,
                'provider' => 'failover_ai',
                'action_taken' => 'blocked'
            ]);

            // Deduct reputation trust score
            $trustScore = \App\Models\UserTrustScore::firstOrCreate(
                ['user_id' => $user->id],
                ['trust_score' => 100, 'violation_count' => 0]
            );
            
            $trustScore->violation_count += 1;
            $trustScore->trust_score = max(0, $trustScore->trust_score - 15); // deduct 15 points per violation
            
            if ($trustScore->trust_score < 40) {
                $trustScore->risk_level = 'high';
            } elseif ($trustScore->trust_score < 75) {
                $trustScore->risk_level = 'medium';
            }
            
            $trustScore->save();

            return [
                'blocked' => true,
                'violations' => $violations,
                'explanation' => $explanation ?? 'Sharing phone numbers, emails, WhatsApp, Discord, or external payment requests outside ' . (Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'the platform')) . ' is strictly forbidden to protect candidates and employers.'
            ];
        }

        return ['blocked' => false];
    }

    /**
     * Get the lightweight total unread messages count for the logged-in user.
     */
    public function getUnreadCount()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => false, 'unread_count' => 0], 401);
            }
            
            // Find all conversations where the user is either candidate or employer
            $conversationIds = Conversation::where('employer_id', $user->id)
                ->orWhere('candidate_id', $user->id)
                ->pluck('id');
            
            // Count all unread messages in those conversations that were NOT sent by the current user
            $unreadCount = Message::whereIn('conversation_id', $conversationIds)
                ->where('sender_id', '!=', $user->id)
                ->where('is_read', false)
                ->count();
                
            return response()->json([
                'status' => true,
                'unread_count' => $unreadCount
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Unread Count Error: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return response()->json(['status' => false, 'message' => 'Error counting unread messages', 'unread_count' => 0], 500);
        }
    }

    /**
     * Delete a single message (only by the sender).
     */
    public function deleteMessage(Request $request, $uuid, $messageId)
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::where('uuid', $uuid)->first();

            if (!$conversation) {
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            if ($conversation->employer_id !== $user->id && $conversation->candidate_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $message = Message::where('id', $messageId)
                ->where('conversation_id', $conversation->id)
                ->first();

            if (!$message) {
                return response()->json(['status' => false, 'message' => 'Message not found'], 404);
            }

            if ($message->sender_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'You can only delete your own messages'], 403);
            }

            $message->delete();

            return response()->json(['status' => true, 'message' => 'Message deleted']);
        } catch (\Throwable $e) {
            Log::error('Delete Message Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error deleting message'], 500);
        }
    }

    /**
     * Delete an entire conversation thread (soft: only hides for the user).
     */
    public function deleteConversation($uuid)
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::where('uuid', $uuid)->first();

            if (!$conversation) {
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            if ($conversation->employer_id !== $user->id && $conversation->candidate_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            // Soft delete: add user to deleted_by array
            $deletedBy = $conversation->deleted_by ?? [];
            if (!in_array($user->id, $deletedBy)) {
                $deletedBy[] = $user->id;
            }
            $conversation->deleted_by = $deletedBy;
            $conversation->save();

            return response()->json(['status' => true, 'message' => 'Conversation deleted']);
        } catch (\Throwable $e) {
            Log::error('Delete Conversation Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error deleting conversation'], 500);
        }
    }

    /**
     * Toggle mute/unmute for a conversation.
     */
    public function toggleMute($uuid)
    {
        try {
            $user = Auth::user();
            $conversation = Conversation::where('uuid', $uuid)->first();

            if (!$conversation) {
                return response()->json(['status' => false, 'message' => 'Conversation not found'], 404);
            }

            if ($conversation->employer_id !== $user->id && $conversation->candidate_id !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $mutedBy = $conversation->muted_by ?? [];
            $isMuted = in_array($user->id, $mutedBy);

            if ($isMuted) {
                $mutedBy = array_values(array_filter($mutedBy, fn($id) => $id !== $user->id));
            } else {
                $mutedBy[] = $user->id;
            }

            $conversation->muted_by = $mutedBy;
            $conversation->save();

            return response()->json([
                'status' => true,
                'muted' => !$isMuted,
                'message' => !$isMuted ? 'Conversation muted' : 'Conversation unmuted'
            ]);
        } catch (\Throwable $e) {
            Log::error('Toggle Mute Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error toggling mute'], 500);
        }
    }

    /**
     * Block a User from Messaging
     */
    public function blockUser(Request $request, $targetUserId)
    {
        try {
            $user = Auth::user();

            if ($targetUserId == $user->id) {
                return response()->json(['status' => false, 'message' => 'Cannot block yourself'], 422);
            }

            $blockedIds = $user->blocked_users ?? [];
            if (in_array($targetUserId, $blockedIds)) {
                return response()->json(['status' => false, 'message' => 'User already blocked'], 422);
            }

            $blockedIds[] = $targetUserId;
            $user->blocked_users = $blockedIds;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User blocked successfully'
            ]);
        } catch (\Throwable $e) {
            Log::error('Block User Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error blocking user'], 500);
        }
    }

    /**
     * Report a User or Message
     */
    public function reportUser(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'reportable_type' => 'required|string|in:user,message,conversation',
                'reportable_id' => 'required|integer',
                'reason' => 'required|string|in:spam,harassment,inappropriate,fraud,other',
                'description' => 'nullable|string|max:1000',
            ]);

            $report = \App\Models\Report::create([
                'reporter_id' => $user->id,
                'reportable_type' => $validated['reportable_type'] === 'user' ? \App\Models\User::class
                    : ($validated['reportable_type'] === 'message' ? \App\Models\Message::class : \App\Models\Conversation::class),
                'reportable_id' => $validated['reportable_id'],
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Report submitted successfully. Our team will review it.',
                'data' => $report,
            ]);
        } catch (\Throwable $e) {
            Log::error('Report User Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error submitting report'], 500);
        }
    }

    /**
     * Download message attachment by storage path.
     */
    public function downloadAttachment($path)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $relativePath = urldecode($path);
            $fullPath = storage_path('app/public/' . $relativePath);

            if (!file_exists($fullPath)) {
                return response()->json(['status' => false, 'message' => 'File not found'], 404);
            }

            return response()->file($fullPath);
        } catch (\Throwable $e) {
            Log::error('Download Attachment Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Error downloading attachment'], 500);
        }
    }

    /**
     * Convert image to webp using GD library.
     * Returns relative storage path or null on failure.
     */
    private function convertToWebp($file): ?string
    {
        try {
            $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
            if (!$source) return null;

            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time() . '.webp';
            $path = storage_path('app/public/chat_attachments');
            if (!is_dir($path)) mkdir($path, 0755, true);
            $fullPath = $path . '/' . $filename;

            $saved = imagewebp($source, $fullPath, 85);
            imagedestroy($source);

            return $saved ? 'chat_attachments/' . $filename : null;
        } catch (\Throwable $e) {
            Log::error('WebP conversion failed: ' . $e->getMessage());
            return null;
        }
    }
}