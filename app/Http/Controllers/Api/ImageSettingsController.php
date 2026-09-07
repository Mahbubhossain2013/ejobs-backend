<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\MediaOptimization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImageSettingsController extends Controller
{
    /**
     * Get all image optimization settings.
     */
    public function getSettings()
    {
        try {
            $keys = [
                'image_enable_optimization',
                'image_enable_webp',
                'image_compression_quality',
                'image_max_upload_size',
                'image_max_dimensions',
                'image_enable_watermark',
                'image_watermark_text',
                'image_watermark_opacity',
                'image_watermark_position',
                'image_enable_queue',
            ];

            $settings = Setting::whereIn('key', $keys)->get()->pluck('value', 'key');

            // Default values if not set
            $defaults = [
                'image_enable_optimization' => '1',
                'image_enable_webp' => '1',
                'image_compression_quality' => '80',
                'image_max_upload_size' => '5120',
                'image_max_dimensions' => '2000',
                'image_enable_watermark' => '0',
                'image_watermark_text' => 'JobBazar',
                'image_watermark_opacity' => '60',
                'image_watermark_position' => 'bottom-right',
                'image_enable_queue' => '0',
            ];

            $data = array_merge($defaults, $settings->toArray());

            return response()->json([
                'status' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error("Get Image Settings Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to load image settings.'
            ], 500);
        }
    }

    /**
     * Update image settings.
     */
    public function updateSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'image_enable_optimization' => 'required|in:0,1',
                'image_enable_webp' => 'required|in:0,1',
                'image_compression_quality' => 'required|integer|min:10|max:100',
                'image_max_upload_size' => 'required|integer|min:100|max:20480',
                'image_max_dimensions' => 'required|integer|min:200|max:10000',
                'image_enable_watermark' => 'required|in:0,1',
                'image_watermark_text' => 'nullable|string|max:100',
                'image_watermark_opacity' => 'required|integer|min:0|max:100',
                'image_watermark_position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
                'image_enable_queue' => 'required|in:0,1',
            ]);

            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => (string)($value ?? '')]
                );
            }

            return response()->json([
                'status' => true,
                'message' => 'Image settings updated successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error("Update Image Settings Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update image settings.'
            ], 500);
        }
    }

    /**
     * Get image analytics dashboard details.
     */
    public function getAnalytics()
    {
        try {
            $totalOptimized = MediaOptimization::count();
            $totalOriginalSize = MediaOptimization::sum('original_size');
            $totalOptimizedSize = MediaOptimization::sum('optimized_size');
            $savedBytes = MediaOptimization::sum('saved_bytes');
            $failedCount = MediaOptimization::where('status', 'failed')->count();

            // Calculate compression efficiency percentage
            $efficiency = $totalOriginalSize > 0 
                ? round(($savedBytes / $totalOriginalSize) * 100, 2) 
                : 0;

            // Most uploaded formats
            $formats = MediaOptimization::select('format', DB::raw('count(*) as count'))
                ->groupBy('format')
                ->orderBy('count', 'desc')
                ->get();

            // Recent activity
            $recent = MediaOptimization::orderBy('created_at', 'desc')
                ->take(8)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'original_name' => $item->original_name,
                        'format' => $item->format,
                        'original_kb' => round($item->original_size / 1024, 2),
                        'optimized_kb' => round($item->optimized_size / 1024, 2),
                        'saved_kb' => round($item->saved_bytes / 1024, 2),
                        'status' => $item->status,
                        'created_at' => $item->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => [
                    'total_images' => $totalOptimized,
                    'storage_saved_mb' => round($savedBytes / (1024 * 1024), 2),
                    'total_original_mb' => round($totalOriginalSize / (1024 * 1024), 2),
                    'total_optimized_mb' => round($totalOptimizedSize / (1024 * 1024), 2),
                    'efficiency_percentage' => $efficiency,
                    'failed_conversions' => $failedCount,
                    'formats_distribution' => $formats,
                    'recent_optimizations' => $recent
                ]
            ]);
        } catch (\Exception $e) {
            Log::error("Get Image Analytics Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to load image analytics details.'
            ], 500);
        }
    }
}
