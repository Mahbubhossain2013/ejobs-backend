<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSecurityLog;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    /**
     * Get list of security & audit logs
     */
    public function getLogs(Request $request)
    {
        $query = UserSecurityLog::with('user');

        if ($request->has('activity_type')) {
            $query->where('activity_type', $request->input('activity_type'));
        }

        if ($request->has('risk_min')) {
            $query->where('risk_score', '>=', $request->input('risk_min'));
        }

        if ($request->input('vpn_only') === 'true' || $request->input('vpn_only') == 1) {
            $query->where('vpn_detected', true);
        }

        $logs = $query->latest()->paginate(30);

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }

    /**
     * Apply or modify a user's restriction/ban status
     */
    public function updateRestriction(Request $request, $userId)
    {
        $request->validate([
            'restriction_status' => 'required|string|in:active,shadow_restricted,suspended',
            'ban_status' => 'required|boolean',
            'moderation_notes' => 'nullable|string',
        ]);

        $user = User::findOrFail($userId);
        $profile = $user->profile;
        $company = $user->company;

        if (!$profile && !$company) {
            // Guarantee profile exists if not defined
            $profile = $user->profile()->create();
        }

        if ($profile) {
            $profile->update([
                'restriction_status' => $request->input('restriction_status'),
                'ban_status' => $request->input('ban_status'),
                'moderation_notes' => $request->input('moderation_notes'),
            ]);
        }

        if ($company) {
            $company->update([
                'restriction_status' => $request->input('restriction_status'),
                'ban_status' => $request->input('ban_status'),
                'moderation_notes' => $request->input('moderation_notes'),
            ]);
        }

        // Write security log event representing manual override adjustments
        UserSecurityLog::create([
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'activity_type' => 'moderation_action',
            'risk_score' => 0,
            'vpn_detected' => false,
            'moderation_details' => sprintf(
                "Moderator action: Status set to %s, Ban set to %d. Notes: %s",
                $request->input('restriction_status'),
                $request->input('ban_status'),
                $request->input('moderation_notes')
            )
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User security profiles updated successfully.',
            'data' => [
                'user_id' => $user->id,
                'restriction_status' => $request->input('restriction_status'),
                'ban_status' => $request->input('ban_status'),
            ]
        ]);
    }

    /**
     * Get specific user profile security stats
     */
    public function getUserSecurityDetails($userId)
    {
        $user = User::with(['profile', 'company'])->findOrFail($userId);
        
        $logs = UserSecurityLog::where('user_id', $userId)->latest()->limit(15)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'security_profile' => [
                    'last_login_ip' => $user->profile?->last_login_ip ?? $user->company?->last_login_ip,
                    'restriction_status' => $user->profile?->restriction_status ?? $user->company?->restriction_status ?? 'active',
                    'ban_status' => (bool)($user->profile?->ban_status ?? $user->company?->ban_status ?? false),
                    'moderation_notes' => $user->profile?->moderation_notes ?? $user->company?->moderation_notes,
                    'device_history' => $user->profile?->login_device_history ?? $user->company?->login_device_history ?? [],
                ],
                'recent_logs' => $logs
            ]
        ]);
    }
}
