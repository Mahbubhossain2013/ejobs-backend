<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Fetch latest 20 notifications without pagination wrapper
        $notifications = $user->notifications()->take(20)->get();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['status' => true, 'message' => 'Marked as read']);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['status' => true, 'message' => 'All marked as read']);
    }

    public function bulkRead(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string'
        ]);

        Auth::user()->unreadNotifications()
            ->whereIn('id', $request->input('ids'))
            ->get()
            ->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Selected notifications marked as read'
        ]);
    }
}