<?php

namespace App\Console\Commands;

use App\Services\ResourceUsageService;
use App\Jobs\ResourceUsageJob;
use Illuminate\Console\Command;
use Exception;

class CheckResourceUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resource:check
                            {--sync : Run synchronously instead of dispatching a job}
                            {--queue : Force dispatch as a job even when sync is available}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check resource usage (file count, disk usage, available inodes and space)';

    /**
     * Execute the console command.
     */
    public function handle(ResourceUsageService $service): int
    {
        $sync = $this->option('sync');
        $queue = $this->option('queue');

        try {
            if ($sync && !$queue) {
                // Run synchronously
                $this->info('Running resource usage check synchronously...');

                $resourceUsage = $service->checkResourceUsage();

                $this->info('Resource usage check completed successfully!');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Base Path', $resourceUsage->base_path],
                        ['File Count', number_format($resourceUsage->file_count)],
                        ['Disk Usage (MB)', number_format($resourceUsage->disk_usage_mb)],
                        ['Available Inodes', number_format($resourceUsage->available_inode)],
                        ['Available Space (MB)', number_format($resourceUsage->available_space_mb)],
                        ['Checked At', $resourceUsage->checked_at->format('Y-m-d H:i:s')],
                    ]
                );

                return Command::SUCCESS;
            } else {
                // Dispatch as job
                $this->info('Dispatching resource usage check job...');

                ResourceUsageJob::dispatch();

                $this->info('Resource usage check job dispatched successfully!');
                $this->info('Base Path: ' . config('resource_monitoring.base_path'));
                $this->info('Check the logs for job execution details.');

                return Command::SUCCESS;
            }
        } catch (Exception $e) {
            $this->error('Resource usage check failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
