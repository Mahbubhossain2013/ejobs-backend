<?php

namespace App\Services\Search;

use App\Models\Job;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class SearchFallbackService
{
    /**
     * Fast non-blocking health check for Meilisearch server
     */
    public static function isMeilisearchHealthy(): bool
    {
        $host = config('services.meilisearch.host', env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'));
        $parsed = parse_url($host);
        
        $ip = $parsed['host'] ?? '127.0.0.1';
        $port = $parsed['port'] ?? 7700;
        
        try {
            // Rapid socket check with a 0.2-second timeout
            $socket = @fsockopen($ip, $port, $errno, $errstr, 0.2);
            if ($socket) {
                fclose($socket);
                return true;
            }
        } catch (\Exception $e) {
            // Unhealthy
        }
        
        return false;
    }

    /**
     * Resilient search over active jobs list
     */
    public static function searchJobs(string $query, ?string $type = null): Collection
    {
        if (self::isMeilisearchHealthy() && class_exists(\Laravel\Scout\Scout::class)) {
            try {
                // Primary Meilisearch index search via Scout
                $scoutQuery = Job::search($query);
                if ($type) {
                    $scoutQuery->where('job_type', $type);
                }
                return $scoutQuery->where('is_active', true)->take(20)->get();
            } catch (\Exception $e) {
                Log::warning("Meilisearch query failed, degrading to database: " . $e->getMessage());
            }
        }

        // Graceful SQL database fallback
        $builder = Job::where('is_active', true);
        
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('location', 'LIKE', "%{$query}%");
            });
        }
        
        if ($type) {
            $builder->where('job_type', $type);
        }

        return $builder->with('company')->latest()->take(20)->get();
    }

    /**
     * Resilient search over companies
     */
    public static function searchCompanies(string $query): Collection
    {
        if (self::isMeilisearchHealthy() && class_exists(\Laravel\Scout\Scout::class)) {
            try {
                return Company::search($query)->take(20)->get();
            } catch (\Exception $e) {
                Log::warning("Meilisearch company search failed: " . $e->getMessage());
            }
        }

        $builder = Company::query();
        if (!empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%")
                  ->orWhere('industry', 'LIKE', "%{$query}%");
            });
        }

        return $builder->latest()->take(20)->get();
    }
}
