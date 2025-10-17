<?php

namespace App\Jobs;

use App\Services\ResourceUsageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class ResourceUsageJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // No parameters needed - base path comes from config
    }

    /**
     * Execute the job.
     */
    public function handle(ResourceUsageService $service): void
    {
        try {
            $basePath = config('resource_monitoring.base_path');

            Log::info('Starting resource usage check', [
                'base_path' => $basePath
            ]);

            $resourceUsage = $service->checkResourceUsage();

            Log::info('Resource usage check completed successfully', [
                'id' => $resourceUsage->id,
                'base_path' => $resourceUsage->base_path,
                'file_count' => $resourceUsage->file_count,
                'disk_usage_mb' => $resourceUsage->disk_usage_mb,
                'available_inode' => $resourceUsage->available_inode,
                'available_space_mb' => $resourceUsage->available_space_mb,
            ]);
        } catch (Exception $e) {
            Log::error('Resource usage job failed', [
                'error' => $e->getMessage(),
                'base_path' => config('resource_monitoring.base_path'),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Resource usage job failed permanently', [
            'error' => $exception->getMessage(),
            'base_path' => config('resource_monitoring.base_path'),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
