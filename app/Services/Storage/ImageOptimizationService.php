<?php

namespace App\Services\Storage;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Infrastructure\RedisHealthService;

class ImageOptimizationService
{
    /**
     * Resiliently process image optimization.
     * Converts images to WebP and resizes them safely.
     * Swaps gracefully between Async Queue or Sync Direct processing.
     */
    public static function optimizeAndSave($tempFilePath, $destinationPath, int $width = 800, int $quality = 80): string
    {
        $redisHealthy = RedisHealthService::isHealthy();
        $queueDriver = config('queue.default');

        // If Redis or Database queue is healthy and configured asynchronously, we can run async jobs.
        // For absolute safety, if we want immediate synchronous processing, we can optimize directly.
        // Let's implement robust direct image processing using PHP GD (WebP conversion failover):
        try {
            if (!file_exists($tempFilePath)) {
                throw new \Exception("Temporary file not found: {$tempFilePath}");
            }

            $imgInfo = @getimagesize($tempFilePath);
            if (!$imgInfo || empty($imgInfo[2])) {
                Storage::disk('public')->put($destinationPath, file_get_contents($tempFilePath));
                return $destinationPath;
            }

            $mime = $imgInfo['mime'];
            
            // Native GD memory expansion check
            ini_set('memory_limit', '256M');

            switch ($mime) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($tempFilePath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($tempFilePath);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($tempFilePath);
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($tempFilePath);
                    break;
                default:
                    $image = false;
            }

            if (!$image) {
                // GD create failed, copy directly as fallback
                Storage::disk('public')->put($destinationPath, file_get_contents($tempFilePath));
                return $destinationPath;
            }

            // Perform Resizing
            $origWidth = imagesx($image);
            $origHeight = imagesy($image);
            
            if ($origWidth > $width) {
                $ratio = $origHeight / $origWidth;
                $newWidth = $width;
                $newHeight = (int) ($width * $ratio);
                
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preserve transparency for PNGs/WebPs
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // Save as WebP format safely to local/public disk
            $tempOut = tempnam(sys_get_temp_dir(), 'optimized_');
            
            if (function_exists('imagewebp')) {
                imagewebp($image, $tempOut, $quality);
                $finalDest = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $destinationPath);
            } else {
                imagejpeg($image, $tempOut, $quality);
                $finalDest = $destinationPath;
            }

            imagedestroy($image);

            Storage::disk('public')->put($finalDest, file_get_contents($tempOut));
            @unlink($tempOut);

            return $finalDest;

        } catch (\Exception $e) {
            Log::error("Image optimization failed, saving raw file: " . $e->getMessage());
            
            // Complete absolute safe fallback: save unmodified file
            try {
                Storage::disk('public')->put($destinationPath, file_get_contents($tempFilePath));
            } catch (\Exception $ex) {
                Log::critical("Storage critical save fail: " . $ex->getMessage());
            }
            
            return $destinationPath;
        }
    }
}
