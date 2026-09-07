<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with(['replies.user'])
            ->latest()
            ->paginate(min(50, max(1, intval($request->get('per_page', 15)))));

        return response()->json([
            'status' => true,
            'data' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:5',
            'category' => 'required|string|in:general,billing,technical,account',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ticket = DB::transaction(function () use ($request) {
                $ticket = SupportTicket::create([
                    'user_id' => auth()->id(),
                    'subject' => $request->subject,
                    'message' => $request->message,
                    'category' => $request->category,
                    'priority' => $request->priority,
                    'status' => 'open',
                ]);

                SupportTicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'message' => $request->message,
                    'is_admin' => false,
                    'attachments' => $this->normalizeAttachments($request->input('attachments', [])),
                ]);

                return $ticket;
            });

            return response()->json([
                'status' => true,
                'message' => 'Ticket created successfully',
                'data' => $ticket->load('replies.user'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create support ticket: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())
            ->with(['replies.user'])
            ->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Support ticket not found or access denied.',
            ], 404);
        }

        $this->normalizeTicket($ticket);

        return response()->json([
            'status' => true,
            'data' => $ticket,
        ]);
    }

    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Support ticket not found or access denied.',
            ], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'This ticket is closed. You cannot add replies.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|min:2',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reply = DB::transaction(function () use ($ticket, $request) {
                $reply = SupportTicketReply::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'message' => $request->message,
                    'is_admin' => false,
                    'attachments' => $this->normalizeAttachments($request->input('attachments', [])),
                ]);

                $ticket->update(['status' => 'in_progress']);

                return $reply;
            });

            return response()->json([
                'status' => true,
                'message' => 'Reply posted successfully',
                'data' => $reply->load('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to add reply: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function close($id)
    {
        $ticket = SupportTicket::where('user_id', auth()->id())->find($id);

        if (!$ticket) {
            return response()->json([
                'status' => false,
                'message' => 'Support ticket not found or access denied.',
            ], 404);
        }

        if ($ticket->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'This ticket is already closed.',
            ], 422);
        }

        $ticket->update(['status' => 'closed']);

        return response()->json([
            'status' => true,
            'message' => 'Ticket closed successfully',
            'data' => $ticket,
        ]);
    }

    private function normalizeAttachments(array $attachments): array
    {
        $result = [];
        foreach ($attachments as $item) {
            if (!is_string($item)) continue;
            $item = trim($item);
            if ($item === '') continue;
            $result[] = $item;
        }

        return array_values(array_unique($result));
    }

    private function normalizeTicket(SupportTicket $ticket): void
    {
        $ticket->attachments = $ticket->attachments ?? [];
        foreach ($ticket->replies as $reply) {
            $reply->attachments = $reply->attachments ?? [];
        }
    }
}
