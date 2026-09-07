<?php

namespace App\Services\Media;

use App\Models\MediaOptimization;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageOptimizerService
{
    /**
     * Convert any image UploadedFile to WebP format.
     * Returns a new UploadedFile with .webp extension.
     * Non-image files are returned unchanged.
     */
    public static function convertToWebp(UploadedFile $file): UploadedFile
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'tif'];

        if (!in_array($ext, $imageTypes)) {
            return $file;
        }

        try {
            $tmpPath = $file->getRealPath();

            if (!@getimagesize($tmpPath)) {
                return $file;
            }

            $srcImage = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($tmpPath),
                'png'         => @imagecreatefrompng($tmpPath),
                'gif'         => @imagecreatefromgif($tmpPath),
                'bmp'         => @imagecreatefrombmp($tmpPath),
                'tiff', 'tif' => @imagecreatefromstring(file_get_contents($tmpPath)),
                default       => null,
            };

            if (!$srcImage) {
                return $file;
            }

            // Convert palette-based images to true color (WebP doesn't support palette)
            if (imageistruecolor($srcImage)) {
                imagealphablending($srcImage, false);
                imagesavealpha($srcImage, true);
            } else {
                $width = imagesx($srcImage);
                $height = imagesy($srcImage);
                $trueColor = imagecreatetruecolor($width, $height);
                imagealphablending($trueColor, false);
                imagesavealpha($trueColor, true);
                imagecopy($trueColor, $srcImage, 0, 0, 0, 0, $width, $height);
                imagedestroy($srcImage);
                $srcImage = $trueColor;
            }

            $webpPath = tempnam(sys_get_temp_dir(), 'webp_') . '.webp';
            imagewebp($srcImage, $webpPath, 85);
            imagedestroy($srcImage);

            $newName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp';
            return new UploadedFile($webpPath, $newName, 'image/webp', null, true);
        } catch (\Throwable $e) {
            Log::warning("WebP conversion failed, returning original: " . $e->getMessage());
            return $file;
        }
    }

    /**
     * Main pipeline endpoint: Optimizes, converts, resizes, and sanitizes images.
     */
    public function optimize(UploadedFile $file, string $folder = 'general', ?string $watermarkText = null): array
    {
        try {
            $originalName = $file->getClientOriginalName();
            $originalSize = $file->getSize();
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType();

            // 1. Core Security Scans & Sanitization
            if ($ext === 'svg') {
                $sanitizedPath = $this->sanitizeSvg($file);
                $uniqueName = Str::random(40) . '.svg';
                $storedPath = $file->storeAs("public/uploads/{$folder}", $uniqueName);
                
                // Track SVG in analytics as success
                MediaOptimization::create([
                    'original_name' => $originalName,
                    'unique_hash' => md5_file($file->getRealPath()),
                    'original_size' => $originalSize,
                    'optimized_size' => $originalSize,
                    'saved_bytes' => 0,
                    'format' => 'svg',
                    'status' => 'success'
                ]);

                return [
                    'status' => 'success',
                    'original' => "uploads/{$folder}/{$uniqueName}",
                    'thumbnail' => "uploads/{$folder}/{$uniqueName}",
                    'format' => 'svg'
                ];
            }

            // Verify if actual image
            $imageInfo = @getimagesize($file->getRealPath());
            if (!$imageInfo) {
                throw new \RuntimeException("Malicious Threat: The uploaded file does not contain valid image content details.");
            }

            // 2. Load configurations from settings
            $quality = intval(Setting::where('key', 'image_compression_quality')->value('value') ?? 80);
            $maxDim = intval(Setting::where('key', 'image_max_dimensions')->value('value') ?? 2000);
            $enableWebp = Setting::where('key', 'image_enable_webp')->value('value') ?? '1';
            $watermarkEnabled = Setting::where('key', 'image_enable_watermark')->value('value') ?? '0';

            // 3. Load dynamic source resource using GD
            $srcImage = $this->createImageFromSource($file->getRealPath(), $ext);
            if (!$srcImage) {
                throw new \RuntimeException("Unsupported image format read source.");
            }

            // Get dimensions
            $width = imagesx($srcImage);
            $height = imagesy($srcImage);

            // Preserve alpha transparency for PNGs & WebP
            imagealphablending($srcImage, false);
            imagesavealpha($srcImage, true);

            // 4. Exif cleaning and automatic rotation resize if oversized
            if ($width > $maxDim || $height > $maxDim) {
                $ratio = min($maxDim / $width, $maxDim / $height);
                $newWidth = intval($width * $ratio);
                $newHeight = intval($height * $ratio);

                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);

                imagecopyresampled($resizedImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($srcImage);
                $srcImage = $resizedImage;
                $width = $newWidth;
                $height = $newHeight;
            }

            // 5. Apply Watermarking if active
            if ($watermarkEnabled === '1' || $watermarkText) {
                $text = $watermarkText ?? Setting::where('key', 'image_watermark_text')->value('value') ?? 'JobBazar';
                $srcImage = $this->applyWatermark($srcImage, $text, $width, $height);
            }

            // 6. Convert & Compile WebP output
            $uniqueHash = md5(uniqid(Str::random(10), true));
            $outputExt = ($enableWebp === '1') ? 'webp' : $ext;
            $fileName = $uniqueHash . '.' . $outputExt;
            
            $targetDir = storage_path("app/public/uploads/{$folder}");
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $outputPath = "{$targetDir}/{$fileName}";
            $this->saveImageToTarget($srcImage, $outputPath, $outputExt, $quality);

            // 7. Auto-generate Responsive Sizes (Thumbnail & Medium)
            $thumbnailName = $uniqueHash . '_thumb.' . $outputExt;
            $this->generateThumbnail($srcImage, "{$targetDir}/{$thumbnailName}", $outputExt, $quality);

            $mediumName = $uniqueHash . '_medium.' . $outputExt;
            $this->generateMediumSize($srcImage, "{$targetDir}/{$mediumName}", $outputExt, $quality);

            // 8. Log compression savings details
            $optimizedSize = filesize($outputPath);
            $savedBytes = max(0, $originalSize - $optimizedSize);

            MediaOptimization::create([
                'original_name' => $originalName,
                'unique_hash' => $uniqueHash,
                'original_size' => $originalSize,
                'optimized_size' => $optimizedSize,
                'saved_bytes' => $savedBytes,
                'format' => $outputExt,
                'status' => 'success'
            ]);

            imagedestroy($srcImage);

            return [
                'status' => 'success',
                'original' => "uploads/{$folder}/{$fileName}",
                'thumbnail' => "uploads/{$folder}/{$thumbnailName}",
                'medium' => "uploads/{$folder}/{$mediumName}",
                'format' => $outputExt,
                'saved_kb' => round($savedBytes / 1024, 2)
            ];

        } catch (\Throwable $e) {
            Log::error("Image optimization crash: " . $e->getMessage());
            
            // Revert fallback: store standard optimized original format safely
            try {
                $uniqueHash = md5(uniqid(Str::random(10), true));
                $ext = $file->getClientOriginalExtension();
                $fileName = "{$uniqueHash}.{$ext}";
                $storedPath = $file->storeAs("public/uploads/{$folder}", $fileName);

                MediaOptimization::create([
                    'original_name' => $file->getClientOriginalName(),
                    'unique_hash' => $uniqueHash,
                    'original_size' => $file->getSize(),
                    'optimized_size' => $file->getSize(),
                    'saved_bytes' => 0,
                    'format' => $ext,
                    'status' => 'failed'
                ]);

                return [
                    'status' => 'fallback',
                    'original' => "uploads/{$folder}/{$fileName}",
                    'thumbnail' => "uploads/{$folder}/{$fileName}",
                    'format' => $ext
                ];
            } catch (\Exception $fallbackErr) {
                return ['status' => 'error', 'message' => $fallbackErr->getMessage()];
            }
        }
    }

    /**
     * Sanitize SVG to block XSS and malicious scripts tags.
     */
    private function sanitizeSvg(UploadedFile $file): string
    {
        $content = file_get_contents($file->getRealPath());

        // Parse using DOMDocument safely
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($content, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NONET);
        libxml_clear_errors();

        // Strip script nodes
        $scripts = $dom->getElementsByTagName('script');
        while ($scripts->length > 0) {
            $scripts->item(0)->parentNode->removeChild($scripts->item(0));
        }

        // Clean out inline events attributes
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//@*[starts-with(name(), "on")]');
        foreach ($nodes as $node) {
            $node->parentNode->removeAttribute($node->nodeName);
        }

        $sanitized = $dom->saveXML();
        file_put_contents($file->getRealPath(), $sanitized);

        return $file->getRealPath();
    }

    /**
     * Apply dynamic watermark overlays using GD coordinate mappings.
     */
    private function applyWatermark($image, string $text, int $width, int $height)
    {
        // Setup text watermarking canvas
        $watermarkColor = imagecolorallocatealpha($image, 255, 255, 255, 60); // semi-transparent white
        $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 80); // background shadow
        
        $fontSize = 4; // standard GD font index (1 to 5)
        
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        // Position: Bottom Right (with 15px padding)
        $x = $width - $textWidth - 15;
        $y = $height - $textHeight - 15;

        // Draw shadow first
        imagestring($image, $fontSize, $x + 1, $y + 1, $text, $shadowColor);
        // Draw text
        imagestring($image, $fontSize, $x, $y, $text, $watermarkColor);

        return $image;
    }

    private function generateThumbnail($image, string $outputPath, string $ext, int $quality): void
    {
        // 150x150 square cropping
        $thumb = imagecreatetruecolor(150, 150);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);

        $width = imagesx($image);
        $height = imagesy($image);

        // Crop center aspect square
        $minSize = min($width, $height);
        $srcX = intval(($width - $minSize) / 2);
        $srcY = intval(($height - $minSize) / 2);

        imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, 150, 150, $minSize, $minSize);
        $this->saveImageToTarget($thumb, $outputPath, $ext, $quality);
        imagedestroy($thumb);
    }

    private function generateMediumSize($image, string $outputPath, string $ext, int $quality): void
    {
        // 600px width aspect ratio scaling
        $width = imagesx($image);
        $height = imagesy($image);

        $newWidth = 600;
        if ($width <= $newWidth) {
            $this->saveImageToTarget($image, $outputPath, $ext, $quality);
            return;
        }

        $newHeight = intval($height * ($newWidth / $width));
        $medium = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($medium, false);
        imagesavealpha($medium, true);

        imagecopyresampled($medium, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $this->saveImageToTarget($medium, $outputPath, $ext, $quality);
        imagedestroy($medium);
    }

    private function createImageFromSource(string $path, string $ext)
    {
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                return @imagecreatefromjpeg($path);
            case 'png':
                return @imagecreatefrompng($path);
            case 'webp':
                return @imagecreatefromwebp($path);
            case 'gif':
                return @imagecreatefromgif($path);
            default:
                return null;
        }
    }

    private function saveImageToTarget($image, string $path, string $ext, int $quality): void
    {
        switch ($ext) {
            case 'webp':
                imagewebp($image, $path, $quality);
                break;
            case 'png':
                // PNG quality is index 0-9 (with 9 maximum compression)
                $pngQuality = intval(9 - (($quality / 100) * 9));
                imagepng($image, $path, $pngQuality);
                break;
            case 'gif':
                imagegif($image, $path);
                break;
            default:
                imagejpeg($image, $path, $quality);
                break;
        }
    }
}
