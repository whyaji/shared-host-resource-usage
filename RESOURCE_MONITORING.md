# Resource Usage Monitoring System

This Laravel application includes a comprehensive resource monitoring system that tracks file counts, disk usage, available inodes, and available space for specified directories.

## Features

-   **File Count Monitoring**: Counts total files in a directory using `find | wc -l`
-   **Disk Usage Tracking**: Monitors disk usage in MB using `du -sm`
-   **Inode Monitoring**: Tracks available inodes using `df -i`
-   **Space Monitoring**: Monitors available disk space in MB using `df -m`
-   **Daily Scheduled Checks**: Automatically runs daily at 2 AM
-   **Manual API Triggers**: REST API endpoints for manual checks
-   **Console Commands**: Command-line interface for manual operations
-   **Background Job Processing**: Asynchronous processing with retry logic

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Resource Monitoring Configuration
BASE_PATH=/your-default-path
AVAILABLE_INODE=1000000
AVAILABLE_SPACE=50000
```

-   `BASE_PATH`: The directory path to monitor (default: `/your-default-path`)
-   `AVAILABLE_INODE`: Expected available inodes (default: 1000000)
-   `AVAILABLE_SPACE`: Expected available space in MB (default: 50000)

### Configuration File

The system uses `config/resource_monitoring.php` for additional settings including alert thresholds.

## Database Schema

The system creates a `resource_usage` table with the following columns:

-   `id`: Primary key
-   `base_path`: The monitored directory path
-   `file_count`: Total number of files
-   `disk_usage_mb`: Disk usage in megabytes
-   `available_inode`: Available inodes on the filesystem
-   `available_space_mb`: Available space in megabytes
-   `checked_at`: Timestamp when the check was performed
-   `created_at`, `updated_at`: Laravel timestamps

## Usage

### Console Commands

#### Run Resource Check Synchronously

```bash
php artisan resource:check --sync --path=/path/to/directory
```

#### Dispatch Resource Check Job

```bash
php artisan resource:check --path=/path/to/directory
```

#### Command Options

-   `--path=`: Override the configured base path
-   `--sync`: Run synchronously instead of dispatching a job
-   `--queue`: Force dispatch as a job

### API Endpoints

All API endpoints are prefixed with `/api/resource-usage/`:

#### Trigger Resource Check (Async)

```http
POST /api/resource-usage/check
Content-Type: application/json

{
    "base_path": "/path/to/directory"
}
```

#### Trigger Resource Check (Sync)

```http
POST /api/resource-usage/check-sync
Content-Type: application/json

{
    "base_path": "/path/to/directory"
}
```

#### Get Latest Resource Usage

```http
GET /api/resource-usage/latest?base_path=/path/to/directory
```

#### Get Resource Usage History

```http
GET /api/resource-usage/history?base_path=/path/to/directory&days=30
```

#### Get Resource Usage Statistics

```http
GET /api/resource-usage/stats?base_path=/path/to/directory&days=7
```

### Scheduled Tasks

The system automatically runs daily at 2 AM. To ensure the scheduler is running, add this to your crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## System Commands Used

The system executes these commands to gather resource information:

1. **File Count**: `find {path} | wc -l`
2. **Disk Usage**: `du -sm {path}`
3. **Available Inodes**: `df -i {path}`
4. **Available Space**: `df -m {path}`

## Error Handling

-   All commands include comprehensive error handling
-   Failed jobs are retried up to 3 times
-   All errors are logged with detailed information
-   Invalid paths are validated before execution

## Monitoring and Logs

-   Check `storage/logs/laravel.log` for detailed execution logs
-   Job failures are logged with stack traces
-   Successful executions include all collected metrics

## Example Output

When running a synchronous check, you'll see output like:

```
+----------------------+------------------------------------------------------+
| Metric               | Value                                                |
+----------------------+------------------------------------------------------+
| Base Path            | /your-default-path                                    |
| File Count           | 433,737                                              |
| Disk Usage (MB)      | 31,041                                               |
| Available Inodes     | 1,000,000                                            |
| Available Space (MB) | 50,000                                               |
| Checked At           | 2025-10-17 02:00:00                                 |
+----------------------+------------------------------------------------------+
```

## Files Created

-   `app/Models/ResourceUsage.php`: Eloquent model
-   `app/Services/ResourceUsageService.php`: Core service logic
-   `app/Jobs/ResourceUsageJob.php`: Background job
-   `app/Http/Controllers/ResourceUsageController.php`: API controller
-   `app/Console/Commands/CheckResourceUsage.php`: Console command
-   `app/Console/Kernel.php`: Scheduler configuration
-   `database/migrations/2025_10_17_004802_create_resource_usage_table.php`: Database migration
-   `config/resource_monitoring.php`: Configuration file
-   `routes/api.php`: API routes (updated)

## Queue Processing

To process background jobs, run:

```bash
php artisan queue:work
```

For production, consider using a process manager like Supervisor to keep the queue worker running.
