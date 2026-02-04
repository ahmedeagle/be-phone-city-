<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ViewOtoSyncLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oto:view-sync-logs
                            {--lines=50 : Number of log lines to show}
                            {--order= : Filter by order number or ID}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View OTO sync logs from Laravel log files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lines = (int) $this->option('lines');
        $orderFilter = $this->option('order');
        $jsonOutput = $this->option('json');

        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->error('Log file not found: ' . $logPath);
            return Command::FAILURE;
        }

        $this->info('Reading OTO sync logs...');
        $this->newLine();

        // Read log file
        $logContent = File::get($logPath);

        // Split into lines and reverse (newest first)
        $logLines = array_reverse(explode("\n", $logContent));

        // Filter for OTO sync related entries
        $otoLogs = [];
        $count = 0;

        foreach ($logLines as $line) {
            if (stripos($line, 'OTO Sync') !== false
                || stripos($line, 'OTO API') !== false
                || stripos($line, 'OTO shipment') !== false
                || stripos($line, 'SyncOtoShipmentStatusJob') !== false) {

                // Apply order filter if provided
                if ($orderFilter && stripos($line, $orderFilter) === false) {
                    continue;
                }

                $otoLogs[] = $line;
                $count++;

                if ($count >= $lines) {
                    break;
                }
            }
        }

        if (empty($otoLogs)) {
            $this->warn('No OTO sync logs found.');
            if ($orderFilter) {
                $this->comment("Try without --order filter or check if order '{$orderFilter}' exists.");
            }
            return Command::SUCCESS;
        }

        if ($jsonOutput) {
            $this->line(json_encode($otoLogs, JSON_PRETTY_PRINT));
        } else {
            $this->info("Found " . count($otoLogs) . " OTO sync log entries:");
            $this->newLine();

            foreach ($otoLogs as $log) {
                // Color code different log levels
                if (stripos($log, 'ERROR') !== false || stripos($log, 'Failed') !== false) {
                    $this->error($log);
                } elseif (stripos($log, 'WARNING') !== false || stripos($log, 'Warning') !== false) {
                    $this->warn($log);
                } elseif (stripos($log, 'INFO') !== false || stripos($log, 'Sync') !== false) {
                    $this->info($log);
                } else {
                    $this->line($log);
                }
            }
        }

        $this->newLine();
        $this->comment("To view more logs, use: php artisan oto:view-sync-logs --lines=100");
        $this->comment("To filter by order: php artisan oto:view-sync-logs --order=ORD-XXXXX");

        return Command::SUCCESS;
    }
}
