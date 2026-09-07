<?php

namespace App\Jobs;

use App\Services\Media\ImageOptimizerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $tempPath;
    protected string $originalName;
    protected string $folder;
    protected ?string $watermarkText;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tempPath, string $originalName, string $folder = 'general', ?string $watermarkText = null)
    {
        $this->tempPath = $tempPath;
        $this->originalName = $originalName;
        $this->folder = $folder;
        $this->watermarkText = $watermarkText;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizerService $optimizer): void
    {
        try {
            if (!file_exists($this->tempPath)) {
                Log::warning("OptimizeImageJob canceled: Temporary file not found at {$this->tempPath}");
                return;
            }

            // Create fake UploadedFile wrapper to utilize optimizer pipeline safely
            $file = new \Illuminate\Http\UploadedFile(
                $this->tempPath,
                $this->originalName,
                mime_content_type($this->tempPath),
                null,
                true // mark as test to skip standard uploaded validation checks
            );

            $result = $optimizer->optimize($file, $this->folder, $this->watermarkText);
            
            // Delete temp file after optimization
            if (file_exists($this->tempPath)) {
                unlink($this->tempPath);
            }

            Log::info("Async OptimizeImageJob completed for '{$this->originalName}' in {$this->folder}. Status: " . ($result['status'] ?? 'unknown'));

        } catch (\Throwable $e) {
            Log::error("Async OptimizeImageJob Crash: " . $e->getMessage());
            
            if (file_exists($this->tempPath)) {
                unlink($this->tempPath);
            }
        }
    }
}
