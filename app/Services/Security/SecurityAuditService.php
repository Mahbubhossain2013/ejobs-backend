<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Company;
use App\Models\UserSecurityLog;
use App\Models\AdminTask;
use App\Services\Ai\AiManagerService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class SecurityAuditService
{
    /**
     * Audit a login attempt signature against historical device maps.
     * Evaluates Proxy usage, Geo-IP changes, failed rates, and triggers AI actions.
     */
    public static function auditRequest(User $user, Request $request): array
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $fingerprint = hash('sha256', $userAgent . $request->header('Accept-Language', '') . $ip);
        
        $profile = $user->profile;
        $company = $user->company;

        // 1. Resolve Geo-IP location lookup (safe cached or fast external API)
        $currentCountry = self::lookupGeoCountry($ip);

        $deviceHistory = [];
        if ($profile) {
            $deviceHistory = $profile->login_device_history ?? [];
        } elseif ($company) {
            $deviceHistory = $company->login_device_history ?? [];
        }

        $isNewIp = true;
        $isNewDevice = true;
        $lastCountry = $currentCountry; // no geo penalty for first-time login
        
        if (!empty($deviceHistory)) {
            foreach ($deviceHistory as $entry) {
                if (isset($entry['ip']) && $entry['ip'] === $ip) {
                    $isNewIp = false;
                }
                if (isset($entry['user_agent']) && $entry['user_agent'] === $userAgent) {
                    $isNewDevice = false;
                }
                if (isset($entry['country'])) {
                    $lastCountry = $entry['country'];
                }
            }
        }

        // 2. Scan proxies
        $vpnDetected = self::checkVpnHeaders($request);

        // 3. Compute security risk score
        $riskScore = 0;
        $reasons = [];

        if ($isNewIp) {
            $riskScore += 15;
            $reasons[] = 'New Login IP address detected';
        }
        if ($isNewDevice) {
            $riskScore += 20;
            $reasons[] = 'New Client device footprint detected';
        }
        if ($currentCountry !== $lastCountry) {
            $riskScore += 40;
            $reasons[] = "Rapid geo-location change: switch from {$lastCountry} to {$currentCountry}";
        }
        if ($vpnDetected) {
            $riskScore += 25;
            $reasons[] = 'VPN proxy header bypass intercepted';
        }

        $status = 'safe';
        if ($riskScore >= 60) {
            $status = 'high_risk';
        } elseif ($riskScore >= 20) {
            $status = 'uncertain';
        }

        // 4. Log high-risk activity but DO NOT auto-lock accounts
        if ($status === 'high_risk' || $status === 'uncertain') {
            self::logEvent($user->id, $ip, $userAgent, $fingerprint, 'suspicious_login', $riskScore, $vpnDetected, 'Suspicious login detected but auto-lock disabled: ' . implode('; ', $reasons));
        }

        // Log successful security markers
        $activityType = $isNewDevice ? 'new_device_login' : 'successful_login';
        self::logEvent(
            $user->id, 
            $ip, 
            $userAgent, 
            $fingerprint, 
            $activityType, 
            $riskScore, 
            $vpnDetected, 
            empty($reasons) ? 'Login completed under normal parameters.' : implode('; ', $reasons)
        );

        // Update target login profiles
        self::updateLoginProfileHistory($user, $ip, $userAgent, $currentCountry);

        return [
            'status' => 'approved',
            'risk_score' => $riskScore,
            'reasons' => $reasons
        ];
    }

    /**
     * Flush all authentication tokens on password modifications
     */
    public static function invalidateSessionsOnPasswordReset(User $user)
    {
        $user->tokens()->delete();
        
        // Reset active session payload histories
        if ($user->profile) {
            $user->profile->update(['active_sessions' => null]);
        }
        if ($user->company) {
            $user->company->update(['active_sessions' => null]);
        }

        Log::info("Security Audit Service: Invalidated all active device sessions for User ID {$user->id} due to password change.");
    }

    /**
     * Consult AI failover service regarding suspicious profiles
     */
    protected static function consultAiBrain(User $user, int $riskScore, array $reasons, string $ip, string $userAgent): array
    {
        $reasonsStr = implode(', ', $reasons);
        $prompt = "Analyze the following security audit telemetry for User \"{$user->name}\" (ID: {$user->id}, Email: {$user->email}).
        Risk Score calculated: {$riskScore}/100.
        Suspected anomalies: [{$reasonsStr}].
        Client IP: {$ip}, Client UserAgent: {$userAgent}.
        Evaluate whether this represents an account takeover attempt or suspicious VPN bot scraping behavior.
        
        Return ONLY a raw JSON string containing exactly these keys:
        {
            \"action\": \"lock\" or \"allow\",
            \"threat_level\": \"high\" or \"medium\" or \"low\",
            \"confidence\": 0.0 to 1.0,
            \"reasoning\": \"...\"
        }";

        try {
            $aiRes = AiManagerService::ask($prompt, 0.2, 500);
            $clean = trim(str_replace(['```json', '```'], '', $aiRes));
            $data = json_decode($clean, true);

            if (isset($data['action']) && in_array($data['action'], ['lock', 'allow'])) {
                return $data;
            }
        } catch (\Exception $e) {
            Log::warning("Security AI Consulting failed. Falling back to default thresholds: " . $e->getMessage());
        }

        // Safe default: always allow, never auto-lock
        return [
            'action' => 'allow',
            'threat_level' => 'low',
            'confidence' => 0.8,
            'reasoning' => 'Fallback logic: auto-lock disabled for safety.'
        ];
    }

    /**
     * Suspend user profiles or company boards
     */
    public static function lockUserAccount(User $user, string $notes)
    {
        if ($user->profile) {
            $user->profile->update([
                'restriction_status' => 'suspended',
                'moderation_notes' => $notes
            ]);
        }
        if ($user->company) {
            $user->company->update([
                'restriction_status' => 'suspended',
                'moderation_notes' => $notes
            ]);
        }

        app(NotificationService::class)->sendNotification(
            $user,
            'Account Suspended',
            "Your account has been suspended. Reason: {$notes}. Please contact support if you believe this is an error.",
            'security',
            '/dashboard'
        );
    }

    /**
     * Resolve Geo Country based on IP addresses
     */
    public static function lookupGeoCountry(string $ip): string
    {
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local';
        }

        try {
            $res = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($res->successful() && $res->json('status') === 'success') {
                return $res->json('country') ?? 'Unknown';
            }
        } catch (Exception $e) {
            // Safe timeout bypass
        }

        return 'Unknown';
    }

    /**
     * Inspect active proxy headers
     */
    protected static function checkVpnHeaders(Request $request): bool
    {
        $proxyHeaders = ['HTTP_VIA', 'HTTP_X_FORWARDED_FOR', 'HTTP_FORWARDED_FOR', 'HTTP_X_PROXY_ID', 'HTTP_CLIENT_IP'];
        foreach ($proxyHeaders as $header) {
            if ($request->server($header)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Create log in UserSecurityLog model
     */
    public static function logEvent(
        ?int $userId,
        string $ip,
        ?string $userAgent,
        string $fingerprint,
        string $activityType,
        int $riskScore,
        bool $vpnDetected,
        string $details
    ) {
        try {
            UserSecurityLog::create([
                'user_id' => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_fingerprint' => $fingerprint,
                'activity_type' => $activityType,
                'risk_score' => $riskScore,
                'vpn_detected' => $vpnDetected,
                'moderation_details' => $details,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to write UserSecurityLog: " . $e->getMessage());
        }
    }

    /**
     * Update internal login arrays with country properties
     */
    protected static function updateLoginProfileHistory(User $user, string $ip, ?string $userAgent, string $country)
    {
        $payload = [
            'ip' => $ip,
            'user_agent' => $userAgent,
            'country' => $country,
            'timestamp' => now()->toIso8601String()
        ];

        // Process profile
        if ($user->profile) {
            $history = $user->profile->login_device_history ?? [];
            array_unshift($history, $payload);
            $history = array_slice($history, 0, 10);
            
            // Build current session listing details
            $sessions = $user->profile->active_sessions ?? [];
            $sessionFingerprint = md5($userAgent . $ip);
            $sessions[$sessionFingerprint] = [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'country' => $country,
                'last_active' => now()->toIso8601String()
            ];

            $user->profile->update([
                'last_login_ip' => $ip,
                'login_device_history' => $history,
                'active_sessions' => $sessions
            ]);
        }

        // Process company
        if ($user->company) {
            $history = $user->company->login_device_history ?? [];
            array_unshift($history, $payload);
            $history = array_slice($history, 0, 10);
            
            $sessions = $user->company->active_sessions ?? [];
            $sessionFingerprint = md5($userAgent . $ip);
            $sessions[$sessionFingerprint] = [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'country' => $country,
                'last_active' => now()->toIso8601String()
            ];

            $user->company->update([
                'last_login_ip' => $ip,
                'login_device_history' => $history,
                'active_sessions' => $sessions
            ]);
        }
    }
}
