<?php

namespace App\Filament\Pages;

use App\Models\SystemUpdateLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;
use ZipArchive;

class BackupRestore extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'System';

    protected static string $view = 'filament.pages.backup-restore';

    protected static ?string $title = 'Backup & Restore';

    protected static ?string $slug = 'system-settings/backup';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public array $backupLogs = [];

    public function mount(): void
    {
        $this->loadBackupLogs();
    }

    public function loadBackupLogs(): void
    {
        $this->backupLogs = SystemUpdateLog::orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Restore from Backup')
                ->description('Upload a backup file to restore the system')
                ->icon('heroicon-o-arrow-up-circle')
                ->schema([
                    Forms\Components\FileUpload::make('restore_file')
                        ->label('Backup File')
                        ->acceptedFileTypes(['application/json', 'application/zip', 'application/octet-stream', 'text/plain'])
                        ->directory('backups')
                        ->maxSize(52428800)
                        ->helperText('Upload a JSON or ZIP backup file (max 50MB)'),
                ]),
        ])->statePath('data');
    }

    public function databaseBackup()
    {
        try {
            $filename = 'db-backup-' . now()->format('Y-m-d-His') . '.sql';
            $directory = storage_path('app/backups');

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $filepath = $directory . '/' . $filename;

            $dbConfig = config('database.connections.' . config('database.default'));

            if (config('database.default') === 'sqlite') {
                $dbPath = $dbConfig['database'];
                copy($dbPath, $filepath);
            } else {
                $dumper = MySql::create()
                    ->setDbName($dbConfig['database'])
                    ->setUserName($dbConfig['username'])
                    ->setPassword($dbConfig['password'])
                    ->setHost($dbConfig['host'] ?? '127.0.0.1')
                    ->setPort($dbConfig['port'] ?? 3306);

                $dumper->dumpToFile($filepath);
            }

            SystemUpdateLog::create([
                'from_version' => 'backup',
                'to_version' => 'database',
                'status' => 'success',
                'notes' => "Database backup created: {$filename}",
                'ip_address' => request()->ip(),
                'performed_by' => auth()->user()?->name ?? 'System',
            ]);

            $this->loadBackupLogs();

            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/octet-stream',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Database Backup Failed!')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function storageBackup()
    {
        try {
            $filename = 'storage-backup-' . now()->format('Y-m-d-His') . '.zip';
            $directory = storage_path('app/backups');
            $zipPath = $directory . '/' . $filename;

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Could not create ZIP file.');
            }

            $storagePath = storage_path('app');
            $this->addDirectoryToZip($zip, $storagePath, 'storage/app');
            $zip->close();

            SystemUpdateLog::create([
                'from_version' => 'backup',
                'to_version' => 'storage',
                'status' => 'success',
                'notes' => "Storage backup created: {$filename}",
                'ip_address' => request()->ip(),
                'performed_by' => auth()->user()?->name ?? 'System',
            ]);

            $this->loadBackupLogs();

            return response()->download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Storage Backup Failed!')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function fullSystemBackup()
    {
        try {
            $filename = 'full-backup-' . now()->format('Y-m-d-His') . '.zip';
            $directory = storage_path('app/backups');
            $zipPath = $directory . '/' . $filename;

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Could not create ZIP file.');
            }

            $configFiles = [
                '.env',
                'config/app.php',
                'config/database.php',
                'config/mail.php',
                'config/queue.php',
                'config/sanctum.php',
                'config/services.php',
            ];

            foreach ($configFiles as $file) {
                $fullPath = base_path($file);
                if (File::exists($fullPath)) {
                    $zip->addFile($fullPath, 'config/' . basename($file));
                }
            }

            $storagePath = storage_path('app');
            $this->addDirectoryToZip($zip, $storagePath, 'storage/app');

            $dbConfig = config('database.connections.' . config('database.default'));
            $dbDumpPath = $directory . '/temp-db-dump.sql';

            if (config('database.default') === 'sqlite') {
                copy($dbConfig['database'], $dbDumpPath);
            } else {
                $dumper = MySql::create()
                    ->setDbName($dbConfig['database'])
                    ->setUserName($dbConfig['username'])
                    ->setPassword($dbConfig['password'])
                    ->setHost($dbConfig['host'] ?? '127.0.0.1')
                    ->setPort($dbConfig['port'] ?? 3306);

                $dumper->dumpToFile($dbDumpPath);
            }

            $zip->addFile($dbDumpPath, 'database-dump.sql');
            $zip->close();

            if (File::exists($dbDumpPath)) {
                File::delete($dbDumpPath);
            }

            SystemUpdateLog::create([
                'from_version' => 'backup',
                'to_version' => 'full_system',
                'status' => 'success',
                'notes' => "Full system backup created: {$filename}",
                'ip_address' => request()->ip(),
                'performed_by' => auth()->user()?->name ?? 'System',
            ]);

            $this->loadBackupLogs();

            return response()->download($zipPath, $filename, [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Full System Backup Failed!')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    public function restore(): void
    {
        $state = $this->form->getState();

        if (empty($state['restore_file'])) {
            Notification::make()
                ->title('No File Selected')
                ->warning()
                ->body('Please upload a backup file to restore.')
                ->send();
            return;
        }

        try {
            $filePath = storage_path('app/' . $state['restore_file']);

            if (!File::exists($filePath)) {
                throw new \Exception('Backup file not found on server.');
            }

            SystemUpdateLog::create([
                'from_version' => 'restore',
                'to_version' => 'system',
                'status' => 'success',
                'notes' => 'System restore initiated from backup file.',
                'ip_address' => request()->ip(),
                'performed_by' => auth()->user()?->name ?? 'System',
            ]);

            Notification::make()
                ->title('Restore Process Initiated!')
                ->warning()
                ->body('The backup file has been received. A system administrator should verify the restore process.')
                ->send();

            $this->loadBackupLogs();
            $this->form->fill([]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Restore Failed!')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPath): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = $zipPath . '/' . substr($file->getPathname(), strlen($directory) + 1);
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($file->getPathname(), $relativePath);
            }
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasPermissionTo('manage_backups');
    }

    public static function canViewNavigation(): bool
    {
        return false;
    }
}
