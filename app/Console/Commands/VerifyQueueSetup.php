<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyQueueSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify queue setup is correct for notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verifying Queue Setup...');
        $this->newLine();

        $allGood = true;

        // Check 1: Queue Connection
        $this->info('1. Checking queue connection...');
        $connection = config('queue.default');
        if ($connection === 'database') {
            $this->line("   ✅ Queue connection: {$connection}");
        } else {
            $this->line("   ⚠️  Queue connection: {$connection} (database recommended for Hostinger)");
        }
        $this->newLine();

        // Check 2: Jobs Table
        $this->info('2. Checking jobs table...');
        if (Schema::hasTable('jobs')) {
            $jobCount = DB::table('jobs')->count();
            $this->line("   ✅ Jobs table exists ({$jobCount} pending jobs)");
        } else {
            $this->error('   ❌ Jobs table does not exist! Run: php artisan migrate');
            $allGood = false;
        }
        $this->newLine();

        // Check 3: Failed Jobs Table
        $this->info('3. Checking failed_jobs table...');
        if (Schema::hasTable('failed_jobs')) {
            $failedCount = DB::table('failed_jobs')->count();
            if ($failedCount > 0) {
                $this->warn("   ⚠️  Failed jobs table exists ({$failedCount} failed jobs)");
                $this->line("   Check failed jobs: SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;");
            } else {
                $this->line("   ✅ Failed jobs table exists (no failed jobs)");
            }
        } else {
            $this->error('   ❌ Failed jobs table does not exist! Run: php artisan migrate');
            $allGood = false;
        }
        $this->newLine();

        // Check 4: Notifications Table
        $this->info('4. Checking notifications table...');
        if (Schema::hasTable('notifications')) {
            $notificationCount = DB::table('notifications')->count();
            $this->line("   ✅ Notifications table exists ({$notificationCount} total notifications)");
        } else {
            $this->error('   ❌ Notifications table does not exist!');
            $allGood = false;
        }
        $this->newLine();

        // Check 5: Frontend URL Configuration
        $this->info('5. Checking frontend URL configuration...');
        $frontendUrl = config('app.frontend_url');
        if ($frontendUrl && $frontendUrl !== 'http://localhost:3000') {
            $this->line("   ✅ Frontend URL: {$frontendUrl}");
        } else {
            $this->warn("   ⚠️  Frontend URL not configured or using default: {$frontendUrl}");
            $this->line("   Set FRONTEND_URL in .env file");
        }
        $this->newLine();

        // Check 6: Mail Configuration
        $this->info('6. Checking mail configuration...');
        $mailDriver = config('mail.default');
        $mailHost = config('mail.mailers.smtp.host');
        if ($mailDriver && $mailHost) {
            $this->line("   ✅ Mail driver: {$mailDriver}");
            $this->line("   ✅ Mail host: {$mailHost}");
        } else {
            $this->warn("   ⚠️  Mail configuration may be incomplete");
        }
        $this->newLine();

        // Check 7: Queue Worker Status
        $this->info('7. Checking queue worker status...');
        $pendingJobs = DB::table('jobs')->count();
        if ($pendingJobs > 0) {
            $this->warn("   ⚠️  {$pendingJobs} pending jobs in queue");
            $this->line("   Run: php artisan queue:work --stop-when-empty");
        } else {
            $this->line("   ✅ No pending jobs (queue is processing correctly)");
        }
        $this->newLine();

        // Summary
        $this->newLine();
        if ($allGood) {
            $this->info('✅ All checks passed! Queue setup looks good.');
            $this->newLine();
            $this->line('📋 Next steps:');
            $this->line('1. Set up cron job for queue worker (see docs/NOTIFICATION_QUEUE_SETUP.md)');
            $this->line('2. Test notifications: php artisan notifications:test order --order-id=1');
            $this->line('3. Monitor queue: SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;');
        } else {
            $this->error('❌ Some issues found. Please fix them before using queues.');
        }

        return $allGood ? 0 : 1;
    }
}
