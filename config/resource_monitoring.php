<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Resource Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the resource monitoring
    | system that tracks file counts, disk usage, and available resources.
    |
    */

    'base_path' => env('BASE_PATH'),
    'available_inode' => env('AVAILABLE_INODE'),
    'available_space' => env('AVAILABLE_SPACE'), // in MB

    /*
    |--------------------------------------------------------------------------
    | Default Schedule
    |--------------------------------------------------------------------------
    |
    | The default schedule for resource monitoring checks.
    | You can override this in your scheduler.
    |
    */
    'schedule' => [
        'enabled' => true,
        'time' => '02:00', // Daily at 2 AM
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for triggering alerts when resources are running low.
    |
    */
    'thresholds' => [
        'inode_warning' => 100000,    // Warning when inodes drop below this
        'inode_critical' => 50000,    // Critical when inodes drop below this
        'space_warning' => 10000,     // Warning when space drops below this (MB)
        'space_critical' => 5000,     // Critical when space drops below this (MB)
    ],
];
