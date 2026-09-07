<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Infrastructure\RedisHealthService;
use App\Services\Search\SearchFallbackService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceInfrastructure extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.performance-infrastructure';
    protected static ?string $title = 'Performance & Infrastructure';
    protected static ?string $slug = 'system-settings/performance';
    protected static bool $shouldRegisterNavigation = false;

    // Live Health Diagnostic Diagnostics
    public array $redisStatus = [];
    public string $queueDriver = 'database';
    public int $queueSize = 0;
    public string $cacheStore = 'database';
    public string $sessionDriver = 'database';
    public bool $meilisearchStatus = false;
    public bool $websocketStatus = false;
    public bool $mailStatus = false;
    public string $storageDisk = 'local';
    
    // User Settings Controls
    public bool $redisForceDisabled = false;
    public string $secondaryMailHost = '';
    public string $secondaryMailPort = '587';
    public string $secondaryMailUsername = '';
    public string $secondaryMailPassword = '';

    public function mount(): void
    {
        $this->runHealthChecks();

        // Load dynamic priority configs from database
        $this->redisForceDisabled = Setting::where('key', 'redis_force_disabled')->value('value') == '1';
        $this->secondaryMailHost = Setting::where('key', 'secondary_mail_host')->value('value') ?? '';
        $this->secondaryMailPort = Setting::where('key', 'secondary_mail_port')->value('value') ?? '587';
        $this->secondaryMailUsername = Setting::where('key', 'secondary_mail_username')->value('value') ?? '';
        $this->secondaryMailPassword = Setting::where('key', 'secondary_mail_password')->value('value') ?? '';
    }

    /**
     * Run all system diagnostic checks at runtime
     */
    public function runHealthChecks(): void
    {
        // 1. Redis
        $this->redisStatus = RedisHealthService::getStatus();
        
        // 2. Queue
        $this->queueDriver = config('queue.default');
        try {
            $this->queueSize = DB::table('queue_jobs')->count();
        } catch (\Exception $e) {
            $this->queueSize = 0;
        }

        // 3. Cache & Session
        $this->cacheStore = config('cache.default');
        $this->sessionDriver = config('session.driver');

        // 4. Meilisearch
        $this->meilisearchStatus = SearchFallbackService::isMeilisearchHealthy();

        // 5. WebSockets Reverb Port Check
        $this->websocketStatus = false;
        try {
            $socket = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 0.2);
            if ($socket) {
                fclose($socket);
                $this->websocketStatus = true;
            }
        } catch (\Exception $e) {
            // Unhealthy
        }

        // 6. Mail
        $this->mailStatus = false;
        try {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $socket = @fsockopen($host, $port ?: 587, $errno, $errstr, 0.5);
            if ($socket) {
                fclose($socket);
                $this->mailStatus = true;
            }
        } catch (\Exception $e) {
            // Unhealthy
        }

        // 7. Storage Disk
        $this->storageDisk = config('filesystems.default', 'local');
    }

    /**
     * Save Priority configuration changes
     */
    public function saveSettings(): void
    {
        Setting::updateOrCreate(['key' => 'redis_force_disabled'], ['value' => $this->redisForceDisabled ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'secondary_mail_host'], ['value' => $this->secondaryMailHost]);
        Setting::updateOrCreate(['key' => 'secondary_mail_port'], ['value' => $this->secondaryMailPort]);
        Setting::updateOrCreate(['key' => 'secondary_mail_username'], ['value' => $this->secondaryMailUsername]);
        Setting::updateOrCreate(['key' => 'secondary_mail_password'], ['value' => $this->secondaryMailPassword]);

        // Evict cached Redis health key so provider can boot anew
        Cache::driver('file')->forget('redis_health_status');

        Notification::make()
            ->title('Infrastructure priority settings updated!')
            ->success()
            ->send();

        $this->runHealthChecks();
    }

    /**
     * Clear Cache Action
     */
    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            
            Notification::make()->title('Application Cache Flushed!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Cache Clear Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    /**
     * Restart Queue Workers
     */
    public function restartQueues(): void
    {
        try {
            Artisan::call('queue:restart');
            Notification::make()->title('Queue Workers Restart Signal Sent!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Queue Restart Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    /**
     * Flush sessions table
     */
    public function clearSessions(): void
    {
        try {
            // Safely clear other user sessions from database but keep current admin active
            $currentSessionId = session()->getId();
            
            DB::table('sessions')
                ->where('id', '!=', $currentSessionId)
                ->delete();

            Notification::make()->title('User Sessions Cleared!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Session Clear Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    /**
     * Flush Redis DB if active
     */
    public function flushRedis(): void
    {
        try {
            if (!$this->redisStatus['available']) {
                throw new \Exception("Redis connection is offline.");
            }

            // Using raw predis/phpredis via Laravel cache flush
            if (config('cache.default') === 'redis') {
                Cache::flush();
            } else {
                // Call flush directly via Redis facade
                \Illuminate\Support\Facades\Redis::connection()->flushdb();
            }

            Notification::make()->title('Redis Database Flushed!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Redis Flush Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    /**
     * Reimport Meilisearch Scout indices
     */
    public function rebuildSearchIndex(): void
    {
        try {
            if (!$this->meilisearchStatus) {
                throw new \Exception("Meilisearch server is unreachable.");
            }

            Artisan::call('scout:import', ['model' => 'App\Models\Job']);
            Artisan::call('scout:import', ['model' => 'App\Models\Company']);

            Notification::make()->title('Search Indices Rebuilt!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Index Rebuild Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_infrastructure');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}
