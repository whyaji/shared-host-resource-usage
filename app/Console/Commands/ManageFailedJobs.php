<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class ManageFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'failed-jobs:manage
                            {action : The action to perform (list|retry|retry-all|flush|show)}
                            {--id= : Specific failed job ID for retry action}
                            {--filter= : Filter by job class name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage failed jobs for the resource monitoring system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $id = $this->option('id');
        $filter = $this->option('filter');

        switch ($action) {
            case 'list':
                return $this->listFailedJobs($filter);
            case 'retry':
                return $this->retryFailedJob($id);
            case 'retry-all':
                return $this->retryAllFailedJobs($filter);
            case 'flush':
                return $this->flushFailedJobs($filter);
            case 'show':
                return $this->showFailedJob($id);
            default:
                $this->error('Invalid action. Available actions: list, retry, retry-all, flush, show');
                return Command::FAILURE;
        }
    }

    /**
     * List all failed jobs
     */
    private function listFailedJobs(?string $filter = null): int
    {
        $query = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'failed_at')
            ->orderBy('failed_at', 'desc');

        if ($filter) {
            $query->where('payload', 'like', "%{$filter}%");
        }

        $failedJobs = $query->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$failedJobs->count()} failed job(s):");
        $this->newLine();

        $headers = ['ID', 'UUID', 'Connection', 'Queue', 'Failed At'];
        $rows = $failedJobs->map(function ($job) {
            return [
                $job->id,
                substr($job->uuid, 0, 8) . '...',
                $job->connection,
                $job->queue,
                $job->failed_at,
            ];
        })->toArray();

        $this->table($headers, $rows);
        return Command::SUCCESS;
    }

    /**
     * Retry a specific failed job
     */
    private function retryFailedJob(?string $id): int
    {
        if (!$id) {
            $this->error('Job ID is required for retry action. Use --id= option.');
            return Command::FAILURE;
        }

        try {
            $result = Artisan::call('queue:retry', ['id' => $id]);

            if ($result === 0) {
                $this->info("Successfully retried failed job ID: {$id}");
                return Command::SUCCESS;
            } else {
                $this->error("Failed to retry job ID: {$id}");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error retrying job: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Retry all failed jobs
     */
    private function retryAllFailedJobs(?string $filter = null): int
    {
        try {
            $command = 'queue:retry all';
            if ($filter) {
                $command .= " --filter={$filter}";
            }

            $result = Artisan::call($command);

            if ($result === 0) {
                $this->info('Successfully retried all failed jobs.');
                return Command::SUCCESS;
            } else {
                $this->error('Failed to retry some jobs.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Error retrying jobs: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Flush all failed jobs
     */
    private function flushFailedJobs(?string $filter = null): int
    {
        if ($filter) {
            $this->warn("Filtering by '{$filter}' is not supported for flush action.");
        }

        if ($this->confirm('Are you sure you want to delete all failed jobs?')) {
            try {
                $result = Artisan::call('queue:flush');

                if ($result === 0) {
                    $this->info('Successfully flushed all failed jobs.');
                    return Command::SUCCESS;
                } else {
                    $this->error('Failed to flush jobs.');
                    return Command::FAILURE;
                }
            } catch (\Exception $e) {
                $this->error("Error flushing jobs: " . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
    }

    /**
     * Show details of a specific failed job
     */
    private function showFailedJob(?string $id): int
    {
        if (!$id) {
            $this->error('Job ID is required for show action. Use --id= option.');
            return Command::FAILURE;
        }

        $job = DB::table('failed_jobs')->find($id);

        if (!$job) {
            $this->error("Failed job with ID {$id} not found.");
            return Command::FAILURE;
        }

        $this->info("Failed Job Details (ID: {$id}):");
        $this->newLine();

        $this->line("UUID: {$job->uuid}");
        $this->line("Connection: {$job->connection}");
        $this->line("Queue: {$job->queue}");
        $this->line("Failed At: {$job->failed_at}");
        $this->newLine();

        $this->line("Payload:");
        $this->line(json_encode(json_decode($job->payload), JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("Exception:");
        $this->line($job->exception);

        return Command::SUCCESS;
    }
}
