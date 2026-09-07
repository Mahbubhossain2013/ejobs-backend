<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\UserSecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SecurityMonitorMiddleware
{
    /**
     * Intercept, audit, and protect the API pipeline.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $fingerprint = hash('sha256', $userAgent . $request->header('Accept-Language', '') . $ip);

        // 1. Audit Account Ban & Suspension Status
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $profile = $user->profile;
            $company = $user->company;

            $isBanned = ($profile && $profile->ban_status) || ($company && $company->ban_status);
            $isSuspended = ($profile && $profile->restriction_status === 'suspended') || 
                           ($company && $company->restriction_status === 'suspended');

            if ($isBanned || $isSuspended) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been restricted due to violations of our terms of service.',
                    'security_status' => $isBanned ? 'banned' : 'suspended'
                ], 403);
            }
        }

        // 2. Perform Proxy & VPN Header Interception
        $vpnDetected = $this->detectVpnHeaders($request);

        // 3. Brute Force Throttling & Security Audit Trigger
        $rateLimitKey = 'req_limit_' . ($user ? $user->id : $ip);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 120)) {
            // Log brute-force attempts in the security logs
            $this->logSecurityEvent(
                $user ? $user->id : null,
                $ip,
                $userAgent,
                $fingerprint,
                'brute_force',
                40, // high risk score
                $vpnDetected,
                'Rate limit exceeded. Highly frequent client transactions detected.'
            );

            return response()->json([
                'status' => false,
                'message' => 'Too many requests. Please slow down.'
            ], 429);
        }
        RateLimiter::hit($rateLimitKey, 60); // 1-minute decay rate

        // 4. Log suspicious activity (VPN usage or header mismatch)
        if ($vpnDetected) {
            $this->logSecurityEvent(
                $user ? $user->id : null,
                $ip,
                $userAgent,
                $fingerprint,
                'suspicious_ip',
                15, // moderate risk score
                true,
                'Client connection loaded over VPN, TOR node, or proxy gateway.'
            );
        }

        // 5. Update user/company login tracking profiles on success
        if ($user) {
            $this->updateLoginHistory($user, $ip, $userAgent);
        }

        return $next($request);
    }

    /**
     * Detect proxy and VPN headers
     */
    protected function detectVpnHeaders(Request $request): bool
    {
        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_PROXY_ID',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP'
        ];

        foreach ($proxyHeaders as $header) {
            if ($request->server($header) || $request->header(str_replace('HTTP_', '', $header))) {
                return true;
            }
        }

        // Standard cloudflare / load balancer header audit checking for proxy jumps
        if ($request->header('X-Forwarded-For') && count(explode(',', $request->header('X-Forwarded-For'))) > 1) {
            return true;
        }

        return false;
    }

    /**
     * Write record into user_security_logs
     */
    protected function logSecurityEvent(
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
        } catch (\Exception $e) {
            // Keep app faultless in case of db writes issues
        }
    }

    /**
     * Update candidate/employer profile device footprint histories
     */
    protected function updateLoginHistory($user, string $ip, ?string $userAgent)
    {
        try {
            $profile = $user->profile;
            $company = $user->company;

            $payload = [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'timestamp' => now()->toIso8601String()
            ];

            if ($profile) {
                $history = $profile->login_device_history ?? [];
                array_unshift($history, $payload);
                $history = array_slice($history, 0, 10); // Keep last 10 entries

                $profile->update([
                    'last_login_ip' => $ip,
                    'login_device_history' => $history,
                ]);
            }

            if ($company) {
                $history = $company->login_device_history ?? [];
                array_unshift($history, $payload);
                $history = array_slice($history, 0, 10);

                $company->update([
                    'last_login_ip' => $ip,
                    'login_device_history' => $history,
                ]);
            }
        } catch (\Exception $e) {
            // Keep operations seamless
        }
    }
}
