<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:cleanup {--days=7 : Number of days to keep exports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old export files from storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up export files older than {$days} days...");

        $disk = Storage::disk('public');
        $exportPath = 'exports/stockout';

        if (! $disk->exists($exportPath)) {
            $this->info('Export directory does not exist.');

            return Command::SUCCESS;
        }

        $files = $disk->files($exportPath);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));

            if ($lastModified->lt($cutoffDate)) {
                $disk->delete($file);
                $deletedCount++;
                $this->line("Deleted: {$file}");
            }
        }

        $this->info("Cleanup complete. Deleted {$deletedCount} file(s).");

        return Command::SUCCESS;
    }
}
