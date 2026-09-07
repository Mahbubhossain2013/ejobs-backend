<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CronJobMonitor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'System';
    protected static string $view = 'filament.pages.cron-monitor';
    protected static ?string $title = 'Cron Job Settings';
    protected static ?string $slug = 'system-settings/cron-job'; // URL: /admin/system-settings/cron-job
    protected static bool $shouldRegisterNavigation = false;

    public ?string $lastCronRun = null;
    public string $webCronUrl = '';
    public string $fullPathCommand = '';

    public function mount(): void
    {
        $lastRun = Setting::where('key', 'last_cron_run_at')->first();
        if ($lastRun && $lastRun->value) {
            $this->lastCronRun = Carbon::parse($lastRun->value)->diffForHumans();
        } else {
            $this->lastCronRun = 'Never recorded';
        }

        $this->webCronUrl = url('/cron/run/79f9cfa4540d2073fc6be150f5530d6a37ca7987679f09bc66c9fd5476d491d2');
        
        $artisanPath = base_path('artisan');
        if (str_contains($artisanPath, 'payserverfest') || str_contains($artisanPath, 'pay.serverfest.com')) {
            $this->fullPathCommand = '/opt/alt/php82/etc /home/payserverfest/public_html/artisan schedule:run > /dev/null 2>&1';
        } else {
            $this->fullPathCommand = "php {$artisanPath} schedule:run > /dev/null 2>&1";
        }
    }

    public function runCronManually(): void
    {
        try {
            // Run the scheduler
            Artisan::call('schedule:run');
            
            // Log the execution
            Setting::updateOrCreate(
                ['key' => 'last_cron_run_at'],
                ['value' => Carbon::now()->toDateTimeString()]
            );

            $this->lastCronRun = Carbon::now()->diffForHumans();

            Notification::make()
                ->title('Cron Job Executed!')
                ->success()
                ->body('The task scheduler ran successfully in the background.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Manual Cron Execution Failed!')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_cron_jobs');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}