<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\MySql;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'db:backup {--clean}';
    protected $description = 'Create a database backup and optionally upload to S3 + clean old backups.';

    public function handle()
    {
        $this->info('Starting database backup...');

        $backupPath = storage_path('backups');
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fileName = 'backup-' . now()->format('Y-m-d-His') . '.sql';
        $filePath = $backupPath . '/' . $fileName;

        try {
            MySql::create()
                ->setDbName(env('DB_DATABASE'))
                ->setUserName(env('DB_USERNAME'))
                ->setPassword(env('DB_PASSWORD'))
                ->dumpToFile($filePath);

            $this->info("Database backup successful: {$filePath}");

            // Upload to S3 if configured
            if (env('BACKUP_DISK') === 's3' && config('filesystems.disks.s3.key')) {
                $s3Content = file_get_contents($filePath);
                Storage::disk('s3')->put("backups/{$fileName}", $s3Content);
                $this->info("Uploaded to S3: backups/{$fileName}");
            }

            // Clean old local backups (keep 30 days)
            if ($this->option('clean') || env('BACKUP_AUTO_CLEAN', true)) {
                $cutoff = now()->subDays(30);
                $files = glob($backupPath . '/backup-*.sql');
                foreach ($files as $file) {
                    if (filemtime($file) < $cutoff->timestamp) {
                        unlink($file);
                        $this->info("Cleaned old backup: " . basename($file));
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}