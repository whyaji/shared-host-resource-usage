<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ResourceUsage extends Model
{
    protected $table = 'resource_usage';

    protected $fillable = [
        'base_path',
        'file_count',
        'disk_usage_mb',
        'available_inode',
        'available_space_mb',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'file_count' => 'integer',
        'disk_usage_mb' => 'integer',
        'available_inode' => 'integer',
        'available_space_mb' => 'integer',
    ];

    /**
     * Get the latest resource usage record for a specific path
     */
    public static function latestForPath(string $path): ?self
    {
        return static::where('base_path', $path)
            ->orderBy('checked_at', 'desc')
            ->first();
    }

    /**
     * Get resource usage records for a date range
     */
    public static function forDateRange(string $path, Carbon $startDate, Carbon $endDate)
    {
        return static::where('base_path', $path)
            ->whereBetween('checked_at', [$startDate, $endDate])
            ->orderBy('checked_at', 'asc')
            ->get();
    }
}
