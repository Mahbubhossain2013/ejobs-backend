<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class InfrastructureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Session driver is controlled by SESSION_DRIVER in .env (default: database).
        // Do NOT dynamically switch session driver per-request — it causes 419 CSRF
        // token mismatch when the driver flaps between redis/file/database.
        // Cache and queue fallbacks are safe to switch because they don't affect
        // the user's session cookie.
        if (!extension_loaded('redis')) {
            config([
                'cache.default' => 'file',
                'queue.default' => 'database',
            ]);
            return;
        }

        try {
            $redisHealthy = \App\Services\Infrastructure\RedisHealthService::isHealthy();

            if (!$redisHealthy) {
                config([
                    'cache.default' => 'file',
                    'queue.default' => 'database',
                ]);
            }
        } catch (\Throwable $e) {
            config([
                'cache.default' => 'file',
                'queue.default' => 'database',
            ]);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Session driver override removed — always use SESSION_DRIVER from .env
    }
}
