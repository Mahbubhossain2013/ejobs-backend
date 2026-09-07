<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\Notification\NotificationService;
use App\Services\Notification\AdminEmailService;
use App\Models\User;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with(['assignedAdmin'])
            ->latest()
            ->get()
            ->map(function ($ticket) {
                // Attach color properties dynamically for frontend convenience
                $ticket->status_color = $ticket->status_color;
                $ticket->priority_color = $ticket->priority_color;
                return $ticket;
            });

        return response()->json([
            'status' => true,
            'data' => $tickets
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'category' => 'required|string|in:billing,technical,account,ai,general',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'message' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ticket = DB::transaction(function () use ($request) {
                $ticket = SupportTicket::create([
                    'user_id' => auth()->id(),
                    'subject' => $request->subject,
                    'category' => $request->category,
                    'priority' => $request->priority,
                    'status' => 'open',
                ]);

                TicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'sender_id' => auth()->id(),
                    'message' => $request->message,
                    'is_admin_reply' => false,
                ]);

                return $ticket;
            });

            // Notify admins about new ticket
            $user = \App\Models\User::find(auth()->id());
            AdminEmailService::notifyAdmins(
                'New Support Ticket: ' . $request->subject,
                'emails.admin_new_ticket',
                [
                    'userName' => $user->name ?? 'User',
                    'subject' => $request->subject,
                    'priority' => $request->priority,
                    'category' => $request->category,
                    'adminUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/admin/support-tickets',
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Ticket created successfully',
                'data' => $ticket->load(['messages.sender', 'assignedAdmin'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create support ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->with(['messages.sender.profile', 'assignedAdmin'])
            ->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Support ticket not found or access denied.'
            ], 404);
        }

        // Include helper attributes
        $ticket->status_color = $ticket->status_color;
        $ticket->priority_color = $ticket->priority_color;

        return response()->json([
            'status' => true,
            'data' => $ticket
        ]);
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Support ticket not found or access denied.'
            ], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'This ticket is closed. You cannot add replies.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reply = DB::transaction(function () use ($ticket, $request) {
                $reply = TicketMessage::create([
                    'support_ticket_id' => $ticket->id,
                    'sender_id' => auth()->id(),
                    'message' => $request->message,
                    'is_admin_reply' => false,
                ]);

                // Update ticket status back to pending since the client has replied
                $ticket->update(['status' => 'pending']);

                return $reply;
            });

            $ticketOwner = \App\Models\User::find($ticket->user_id);
            if ($ticketOwner) {
                app(NotificationService::class)->sendNotification(
                    $ticketOwner,
                    'Support Ticket Reply',
                    'A new reply has been posted on your support ticket "' . $ticket->subject . '". Please check for updates.',
                    'general',
                    '/support/tickets/' . $ticket->id
                );
            }

            // Notify admins about user reply
            $replyPreview = mb_strimwidth(strip_tags($request->message), 0, 200, '...');
            AdminEmailService::notifyAdmins(
                'New reply on: ' . $ticket->subject,
                'emails.admin_ticket_reply',
                [
                    'userName' => $ticketOwner->name ?? 'User',
                    'subject' => $ticket->subject,
                    'replyPreview' => $replyPreview,
                    'adminUrl' => config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/admin/support-tickets',
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'Reply posted successfully',
                'data' => $reply->load('sender.profile')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add reply: ' . $e->getMessage()
            ], 500);
        }
    }
}
