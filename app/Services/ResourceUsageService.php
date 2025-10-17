<?php

namespace App\Services;

use App\Models\ResourceUsage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Exception;

class ResourceUsageService
{
    /**
     * Check resource usage for the configured path
     */
    public function checkResourceUsage(): ResourceUsage
    {
        $basePath = config('resource_monitoring.base_path');

        if (!$basePath) {
            throw new Exception('Base path not configured. Please set BASE_PATH in your environment variables.');
        }

        if (!is_dir($basePath)) {
            throw new Exception("Directory does not exist: {$basePath}");
        }

        try {
            $fileCount = $this->getFileCount($basePath);
            $diskUsage = $this->getDiskUsage($basePath);
            $availableInode = $this->getAvailableInode($basePath);
            $availableSpace = $this->getAvailableSpace($basePath);

            return ResourceUsage::create([
                'base_path' => $basePath,
                'file_count' => $fileCount,
                'disk_usage_mb' => $diskUsage,
                'available_inode' => $availableInode,
                'available_space_mb' => $availableSpace,
                'checked_at' => Carbon::now(),
            ]);
        } catch (Exception $e) {
            Log::error('Resource usage check failed', [
                'path' => $basePath,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get file count for the specified directory
     */
    private function getFileCount(string $path): int
    {
        $timeout = config('resource_monitoring.timeouts.find_command', 180);
        $result = Process::timeout($timeout)->run("find {$path} | wc -l");

        if (!$result->successful()) {
            throw new Exception("Failed to count files: {$result->errorOutput()}");
        }

        return (int) trim($result->output());
    }

    /**
     * Get disk usage in MB for the specified directory
     */
    private function getDiskUsage(string $path): int
    {
        // Use -m flag for MB output on macOS/Linux
        $timeout = config('resource_monitoring.timeouts.du_command', 300);
        $result = Process::timeout($timeout)->run("du -sm {$path}");

        if (!$result->successful()) {
            throw new Exception("Failed to get disk usage: {$result->errorOutput()}");
        }

        // Parse output like "31041	/your-default-path"
        $output = trim($result->output());
        $parts = preg_split('/\s+/', $output);

        if (count($parts) < 2) {
            throw new Exception("Unexpected disk usage output format: {$output}");
        }

        return (int) $parts[0];
    }

    /**
     * Get available inodes for the filesystem containing the path
     */
    private function getAvailableInode(string $path): int
    {
        // check config env available_inode
        $availableInode = config('resource_monitoring.available_inode');
        if ($availableInode) {
            return (int) $availableInode;
        }

        $timeout = config('resource_monitoring.timeouts.df_command', 60);
        $result = Process::timeout($timeout)->run("df -i {$path}");

        if (!$result->successful()) {
            throw new Exception("Failed to get available inodes: {$result->errorOutput()}");
        }

        $lines = explode("\n", trim($result->output()));

        // Skip header line and get the data line
        if (count($lines) < 2) {
            throw new Exception("Unexpected df output format");
        }

        $dataLine = $lines[1];
        $parts = preg_split('/\s+/', $dataLine);

        if (count($parts) < 4) {
            throw new Exception("Unexpected df output format: {$dataLine}");
        }

        // Available inodes is the 4th column (index 3)
        return (int) $parts[3];
    }

    /**
     * Get available space in MB for the filesystem containing the path
     */
    private function getAvailableSpace(string $path): int
    {
        // Check config env available_space first
        $availableSpace = config('resource_monitoring.available_space');
        if ($availableSpace) {
            return (int) $availableSpace;
        }

        $timeout = config('resource_monitoring.timeouts.df_command', 60);
        $result = Process::timeout($timeout)->run("df -m {$path}");

        if (!$result->successful()) {
            throw new Exception("Failed to get available space: {$result->errorOutput()}");
        }

        $lines = explode("\n", trim($result->output()));

        // Skip header line and get the data line
        if (count($lines) < 2) {
            throw new Exception("Unexpected df output format");
        }

        $dataLine = $lines[1];
        $parts = preg_split('/\s+/', $dataLine);

        if (count($parts) < 4) {
            throw new Exception("Unexpected df output format: {$dataLine}");
        }

        // Available space is the 4th column (index 3)
        return (int) $parts[3];
    }

    /**
     * Get the latest resource usage for the configured path
     */
    public function getLatestUsage(): ?ResourceUsage
    {
        $path = config('resource_monitoring.base_path');
        if (!$path) {
            throw new Exception('Base path not configured. Please set BASE_PATH in your environment variables.');
        }
        return ResourceUsage::latestForPath($path);
    }

    /**
     * Get resource usage history for a date range
     */
    public function getUsageHistory(?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $path = config('resource_monitoring.base_path');
        $startDate = $startDate ?: Carbon::now()->subDays(30);
        $endDate = $endDate ?: Carbon::now();

        return ResourceUsage::forDateRange($path, $startDate, $endDate);
    }
}
