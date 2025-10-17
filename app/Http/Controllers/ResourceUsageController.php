<?php

namespace App\Http\Controllers;

use App\Jobs\ResourceUsageJob;
use App\Services\ResourceUsageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ResourceUsageController extends Controller
{
    protected ResourceUsageService $service;

    public function __construct(ResourceUsageService $service)
    {
        $this->service = $service;
    }

    /**
     * Manually trigger a resource usage check
     */
    public function check(Request $request): JsonResponse
    {
        try {
            // Validate input parameters
            $request->validate([
                'sync' => 'sometimes|boolean'
            ]);

            // If sync is requested, run immediately
            if ($request->input('sync', false)) {
                $resourceUsage = $this->service->checkResourceUsage();

                return response()->json([
                    'success' => true,
                    'message' => 'Resource usage check completed successfully',
                    'data' => $resourceUsage
                ]);
            }

            // Otherwise, dispatch as a job
            ResourceUsageJob::dispatch();

            return response()->json([
                'success' => true,
                'message' => 'Resource usage check job dispatched successfully',
                'data' => [
                    'base_path' => config('resource_monitoring.base_path'),
                    'queued_at' => Carbon::now()->toISOString()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Resource usage check failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to trigger resource usage check'
            ], 500);
        }
    }

    /**
     * Get the latest resource usage data
     */
    public function latest(Request $request): JsonResponse
    {
        try {
            $resourceUsage = $this->service->getLatestUsage();

            if (!$resourceUsage) {
                return response()->json([
                    'success' => false,
                    'message' => 'No resource usage data found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $resourceUsage
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve latest resource usage data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve resource usage data'
            ], 500);
        }
    }

    /**
     * Get resource usage history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            // Validate input parameters
            $request->validate([
                'days' => 'sometimes|integer|min:1|max:365'
            ]);

            $days = $request->input('days', 30);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            $history = $this->service->getUsageHistory($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $history,
                'meta' => [
                    'base_path' => config('resource_monitoring.base_path'),
                    'days' => $days,
                    'start_date' => $startDate->toISOString(),
                    'end_date' => $endDate->toISOString(),
                    'total_records' => $history->count()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve resource usage history', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve resource usage history'
            ], 500);
        }
    }

    /**
     * Get resource usage statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Validate input parameters
            $request->validate([
                'days' => 'sometimes|integer|min:1|max:365'
            ]);

            $days = $request->input('days', 7);
            $startDate = Carbon::now()->subDays($days);
            $endDate = Carbon::now();

            $history = $this->service->getUsageHistory($startDate, $endDate);

            if ($history->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data available for the specified period'
                ], 404);
            }

            $stats = [
                'period' => [
                    'days' => $days,
                    'start_date' => $startDate->toISOString(),
                    'end_date' => $endDate->toISOString()
                ],
                'file_count' => [
                    'current' => $history->last()->file_count,
                    'max' => $history->max('file_count'),
                    'min' => $history->min('file_count'),
                    'avg' => round($history->avg('file_count'), 2)
                ],
                'disk_usage_mb' => [
                    'current' => $history->last()->disk_usage_mb,
                    'max' => $history->max('disk_usage_mb'),
                    'min' => $history->min('disk_usage_mb'),
                    'avg' => round($history->avg('disk_usage_mb'), 2)
                ],
                'available_inode' => [
                    'current' => $history->last()->available_inode,
                    'max' => $history->max('available_inode'),
                    'min' => $history->min('available_inode'),
                    'avg' => round($history->avg('available_inode'), 2)
                ],
                'available_space_mb' => [
                    'current' => $history->last()->available_space_mb,
                    'max' => $history->max('available_space_mb'),
                    'min' => $history->min('available_space_mb'),
                    'avg' => round($history->avg('available_space_mb'), 2)
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            Log::error('Failed to calculate resource usage statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate resource usage statistics'
            ], 500);
        }
    }
}
