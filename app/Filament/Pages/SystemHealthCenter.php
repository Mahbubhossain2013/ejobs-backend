<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\Infrastructure\RedisHealthService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class SystemHealthCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.system-health-center';

    protected static ?string $title = 'System Health Center';

    protected static ?string $slug = 'system-settings/health';

    protected static bool $shouldRegisterNavigation = false;

    public bool $dbConnected = false;

    public string $dbDriver = '';

    public string $dbVersion = '';

    public string $dbError = '';

    public int $queuePending = 0;

    public int $queueFailed = 0;

    public string $queueDriver = '';

    public bool $queueWorkerRunning = false;

    public bool $redisHealthy = false;

    public string $redisHost = '';

    public string $redisPort = '';

    public float $redisLatency = 0;

    public string $redisError = '';

    public bool $mailHealthy = false;

    public string $mailDriver = '';

    public string $mailHost = '';

    public string $mailError = '';

    public bool $schedulerHealthy = false;

    public ?string $lastCronRun = null;

    public string $schedulerStatus = 'Unknown';

    public bool $storageWritable = false;

    public string $storageDiskUsage = '0 MB';

    public string $storageDriver = '';

    public bool $apiHealthy = false;

    public int $apiTokenCount = 0;

    public string $apiError = '';

    public function mount(): void
    {
        $this->runHealthChecks();
    }

    public function runHealthChecks(): void
    {
        $this->checkDatabase();
        $this->checkQueue();
        $this->checkRedis();
        $this->checkMail();
        $this->checkScheduler();
        $this->checkStorage();
        $this->checkApi();
    }

    protected function checkDatabase(): void
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            $this->dbConnected = true;
            $this->dbDriver = $connection->getConfig('driver');
            $this->dbVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $this->dbError = '';
        } catch (\Exception $e) {
            $this->dbConnected = false;
            $this->dbDriver = config('database.default', 'mysql');
            $this->dbVersion = 'N/A';
            $this->dbError = $e->getMessage();
        }
    }

    protected function checkQueue(): void
    {
        $this->queueDriver = config('queue.default', 'database');

        try {
            $this->queuePending = DB::table('jobs')->count();
        } catch (\Exception $e) {
            $this->queuePending = 0;
        }

        try {
            $this->queueFailed = DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            $this->queueFailed = 0;
        }

        $this->queueWorkerRunning = $this->queuePending > 0 || $this->queueDriver === 'redis';
    }

    protected function checkRedis(): void
    {
        $status = RedisHealthService::getStatus();
        $this->redisHealthy = $status['available'];
        $this->redisHost = $status['host'];
        $this->redisPort = $status['port'];
        $this->redisLatency = $status['latency_ms'];
        $this->redisError = $status['error'] ?? '';
    }

    protected function checkMail(): void
    {
        $this->mailDriver = config('mail.default', 'smtp');
        $this->mailHost = config('mail.mailers.smtp.host', 'N/A');

        try {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port', 587);
            $socket = @fsockopen($host, $port, $errno, $errstr, 2.0);
            if ($socket) {
                fclose($socket);
                $this->mailHealthy = true;
                $this->mailError = '';
            } else {
                $this->mailHealthy = false;
                $this->mailError = $errstr ?: 'Connection refused';
            }
        } catch (\Exception $e) {
            $this->mailHealthy = false;
            $this->mailError = $e->getMessage();
        }
    }

    protected function checkScheduler(): void
    {
        $lastRun = Setting::where('key', 'last_cron_run_at')->first();
        if ($lastRun && $lastRun->value) {
            $this->lastCronRun = \Carbon\Carbon::parse($lastRun->value)->diffForHumans();
            $minutesAgo = \Carbon\Carbon::parse($lastRun->value)->diffInMinutes(now());
            $this->schedulerHealthy = $minutesAgo < 15;
            $this->schedulerStatus = $this->schedulerHealthy ? 'Healthy' : 'Overdue';
        } else {
            $this->lastCronRun = 'Never executed';
            $this->schedulerHealthy = false;
            $this->schedulerStatus = 'No Data';
        }
    }

    protected function checkStorage(): void
    {
        $this->storageDriver = config('filesystems.default', 'local');

        try {
            $storagePath = storage_path('app');
            $this->storageWritable = is_writable($storagePath);
        } catch (\Exception $e) {
            $this->storageWritable = false;
        }

        try {
            $totalBytes = 0;
            $path = storage_path('app');
            if (is_dir($path)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $totalBytes += $file->getSize();
                    }
                }
            }
            $this->storageDiskUsage = $this->formatBytes($totalBytes);
        } catch (\Exception $e) {
            $this->storageDiskUsage = 'N/A';
        }
    }

    protected function checkApi(): void
    {
        try {
            $this->apiTokenCount = DB::table('personal_access_tokens')->count();
            $this->apiHealthy = true;
            $this->apiError = '';
        } catch (\Exception $e) {
            $this->apiHealthy = false;
            $this->apiTokenCount = 0;
            $this->apiError = $e->getMessage();
        }
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

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

    public function restartQueue(): void
    {
        try {
            Artisan::call('queue:restart');
            Notification::make()->title('Queue Workers Restarted!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Queue Restart Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    public function flushRedis(): void
    {
        try {
            if (!$this->redisHealthy) {
                throw new \Exception('Redis connection is offline.');
            }

            if (config('cache.default') === 'redis') {
                Cache::flush();
            } else {
                Redis::connection()->flushdb();
            }

            Notification::make()->title('Redis Flushed Successfully!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Redis Flush Failed!')->danger()->body($e->getMessage())->send();
        }
    }

    public function runScheduler(): void
    {
        try {
            Artisan::call('schedule:run');
            Setting::updateOrCreate(
                ['key' => 'last_cron_run_at'],
                ['value' => now()->toDateTimeString()]
            );
            $this->checkScheduler();
            Notification::make()->title('Scheduler Executed!')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Scheduler Failed!')->danger()->body($e->getMessage())->send();
        }
    }
}
