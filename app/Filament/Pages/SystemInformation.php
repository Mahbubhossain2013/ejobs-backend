<?php

namespace App\Filament\Pages;

use App\Models\SystemVersion;
use App\Services\Infrastructure\RedisHealthService;
use Composer\InstalledVersions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemInformation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.system-information';

    protected static ?string $title = 'System Information';

    protected static ?string $slug = 'system-settings/system-info';

    protected static bool $shouldRegisterNavigation = false;

    public string $currentVersion = '1.0.0';

    public string $buildNumber = 'N/A';

    public ?string $releaseDate = null;

    public string $environment = 'production';

    public string $phpVersion = '';

    public string $laravelVersion = '';

    public string $filamentVersion = '';

    public string $reactVersion = 'N/A';

    public string $serverSoftware = 'N/A';

    public string $mysqlVersion = 'N/A';

    public bool $redisAvailable = false;

    public string $diskUsage = 'N/A';

    public string $memoryUsage = 'N/A';

    public string $totalMemory = 'N/A';

    public function mount(): void
    {
        $this->gatherVersionInfo();
        $this->gatherServerInfo();
        $this->gatherResourceInfo();
    }

    protected function gatherVersionInfo(): void
    {
        try {
            $version = SystemVersion::current();
            if ($version) {
                $this->currentVersion = $version->version;
                $this->buildNumber = $version->build_number ?? 'N/A';
                $this->releaseDate = $version->installed_at
                    ? $version->installed_at->format('M d, Y h:i A')
                    : 'N/A';
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        $this->phpVersion = PHP_VERSION;
        $this->laravelVersion = app()->version();
        try {
            $this->filamentVersion = InstalledVersions::getPrettyVersion('filament/filament');
        } catch (\Exception $e) {
            $this->filamentVersion = 'v3.x';
        }
        $this->environment = config('app.env', 'production');

        $this->reactVersion = $this->getReactVersion();
    }

    protected function gatherServerInfo(): void
    {
        $this->serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A (CLI)';

        try {
            $this->mysqlVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'N/A';
        } catch (\Exception $e) {
            $this->mysqlVersion = 'N/A';
        }

        $this->redisAvailable = RedisHealthService::isHealthy();
    }

    protected function gatherResourceInfo(): void
    {
        $this->memoryUsage = $this->formatBytes(memory_get_usage(true));
        $this->totalMemory = $this->formatBytes(memory_get_usage(true) + memory_get_usage(false));

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
            $this->diskUsage = $this->formatBytes($totalBytes);
        } catch (\Exception $e) {
            $this->diskUsage = 'N/A';
        }
    }

    protected function getReactVersion(): string
    {
        $packagePath = base_path('package.json');
        if (File::exists($packagePath)) {
            $package = json_decode(File::get($packagePath), true);
            $deps = array_merge(
                $package['dependencies'] ?? [],
                $package['devDependencies'] ?? []
            );

            return $deps['react'] ?? 'N/A';
        }

        return 'N/A';
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
}
