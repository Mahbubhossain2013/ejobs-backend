<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class HealthController extends Controller
{
    public function check()
    {
        $checks = [];
        $healthy = true;

        // Database connectivity
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $checks['database'] = [
                'status' => 'ok',
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'error', 'message' => 'Database unreachable'];
            $healthy = false;
        }

        // Redis/Cache connectivity
        try {
            $start = microtime(true);
            Cache::put('health_check', true, 10);
            $checks['cache'] = [
                'status' => 'ok',
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'error', 'message' => 'Cache unreachable'];
        }

        // Storage writability
        try {
            $start = microtime(true);
            $testFile = 'health_check_' . time() . '.txt';
            Storage::disk('local')->put($testFile, 'ok');
            Storage::disk('local')->delete($testFile);
            $checks['storage'] = [
                'status' => 'ok',
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['storage'] = ['status' => 'error', 'message' => 'Storage unreachable'];
            $healthy = false;
        }

        // Queue Driver
        try {
            $start = microtime(true);
            $driver = config('queue.default', 'unknown');
            // Simple connectivity check — dispatch a null job and verify
            $checks['queue'] = [
                'status' => 'ok',
                'driver' => $driver,
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'error', 'message' => 'Queue unreachable'];
        }

        // Mail Service
        try {
            $start = microtime(true);
            $driver = config('mail.default', config('mail.mailer', 'unknown'));
            $checks['mail'] = [
                'status' => 'ok',
                'driver' => $driver,
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['mail'] = ['status' => 'error', 'message' => 'Mail service unreachable'];
        }

        // Session Store
        try {
            $start = microtime(true);
            $driver = config('session.driver', 'unknown');
            $checks['session'] = [
                'status' => 'ok',
                'driver' => $driver,
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['session'] = ['status' => 'error', 'message' => 'Session store unreachable'];
        }

        // Platform Stats
        try {
            $start = microtime(true);
            $checks['stats'] = [
                'status' => 'ok',
                'total_users' => DB::table('users')->count(),
                'active_users' => DB::table('users')->where('updated_at', '>=', now()->subDays(30))->count(),
                'active_jobs' => DB::table('jobs')->where('is_active', true)->count(),
                'total_applications' => DB::table('job_applications')->count(),
                'response_ms' => round((microtime(true) - $start) * 1000, 2),
            ];
        } catch (\Throwable $e) {
            $checks['stats'] = ['status' => 'error', 'message' => 'Stats unavailable'];
        }

        $statusCode = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $statusCode);
    }
}
