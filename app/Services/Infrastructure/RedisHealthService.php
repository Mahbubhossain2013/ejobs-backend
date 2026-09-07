<?php

namespace App\Services\Infrastructure;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RedisHealthService
{
    protected static ?bool $cachedHealth = null;

    /**
     * Rapid socket health check to verify if Redis is up without blocking the PHP thread.
     * Caches status in file cache to prevent TCP socket overhead on standard requests.
     */
    public static function isHealthy(): bool
    {
        // 1. Lifecycle caching to prevent checking multiple times in the same HTTP request
        if (self::$cachedHealth !== null) {
            return self::$cachedHealth;
        }

        // 2. Short-lived file caching (5 seconds) to avoid TCP socket overhead on every request
        try {
            $cacheStore = Cache::driver('file');
            $healthStatus = $cacheStore->get('redis_health_status');
            
            if ($healthStatus !== null) {
                self::$cachedHealth = (bool) $healthStatus;
                return self::$cachedHealth;
            }
        } catch (\Throwable $e) {
            // Fallback if cache driver is not available yet
        }

        // 3. Retrieve connection details dynamically from configuration
        $host = config('database.redis.default.host', '127.0.0.1');
        $port = config('database.redis.default.port', '6379');

        $healthy = false;
        
        try {
            // Fast TCP socket check with 0.5-second timeout (fully non-blocking)
            $socket = @fsockopen($host, $port, $errno, $errstr, 0.5);
            if ($socket) {
                fclose($socket);
                $healthy = true;
            }
        } catch (\Throwable $e) {
            Log::warning("Redis TCP health check failed: " . $e->getMessage());
            $healthy = false;
        }

        // 4. Cache status for 5 seconds
        try {
            if (isset($cacheStore)) {
                $cacheStore->put('redis_health_status', $healthy ? 1 : 0, 5);
            }
        } catch (\Throwable $e) {
            // Ignore cache write errors
        }

        self::$cachedHealth = $healthy;
        return $healthy;
    }

    /**
     * Get detailed health telemetry parameters
     */
    public static function getStatus(): array
    {
        $host = config('database.redis.default.host', '127.0.0.1');
        $port = config('database.redis.default.port', '6379');
        
        $startTime = microtime(true);
        $connected = false;
        $errorMessage = null;
        $latency = 0;

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 1.0);
            if ($socket) {
                fclose($socket);
                $connected = true;
                $latency = round((microtime(true) - $startTime) * 1000, 2);
            } else {
                $errorMessage = $errstr ?: "Connection refused (error code {$errno})";
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        return [
            'available' => $connected,
            'connected' => $connected,
            'healthy' => $connected,
            'host' => $host,
            'port' => $port,
            'latency_ms' => $latency,
            'error' => $errorMessage,
            'last_checked' => now()->toIso8601String()
        ];
    }
}
