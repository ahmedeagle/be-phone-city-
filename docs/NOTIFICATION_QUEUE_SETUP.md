# Notification Queue Setup Guide for Hostinger

This guide explains how to ensure notifications work correctly with Laravel queues on Hostinger hosting.

## Overview

All notifications in this application use Laravel queues (`ShouldQueue` interface) to process notifications asynchronously. This improves performance but requires proper queue worker setup.

## Prerequisites

1. ✅ Database queue driver configured (default: `database`)
2. ✅ Jobs table migration run (`php artisan migrate`)
3. ✅ Failed jobs table exists
4. ✅ Queue worker process running

## Step 1: Verify Queue Configuration

### Check Current Configuration

```bash
php artisan config:show queue
```

Or check your `.env` file:
```env
QUEUE_CONNECTION=database
```

### Verify Jobs Table Exists

```bash
php artisan migrate:status
```

Make sure `0001_01_01_000002_create_jobs_table` is migrated.

## Step 2: Test Notifications Locally

### Test with Queue (Recommended)

```bash
# Test order notification
php artisan notifications:test order --order-id=1

# Test ticket notification
php artisan notifications:test ticket --ticket-id=1

# Test payment notification
php artisan notifications:test payment --order-id=1
```

### Test Synchronously (Bypass Queue)

```bash
php artisan notifications:test order --order-id=1 --sync
```

This helps verify notifications work before testing with queues.

## Step 3: Set Up Queue Worker on Hostinger

### Option A: Cron-Based Queue Worker (Shared Hosting)

This is the recommended approach for Hostinger shared hosting.

#### Create Cron Job in hPanel

1. Log in to **Hostinger hPanel**
2. Navigate to **Advanced** → **Cron Jobs**
3. Click **Create Cron Job**

#### Configure Cron Job

**Command:**
```bash
* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

**Settings:**
- **Minute**: `*` (every minute)
- **Hour**: `*`
- **Day**: `*`
- **Month**: `*`
- **Weekday**: `*`

**Important Notes:**
- Replace `/home/username/public_html` with your actual project path
- Use full PHP path if needed: `/usr/bin/php` or find with `which php`
- The `--stop-when-empty` flag processes all pending jobs then exits
- This runs every minute and processes all queued jobs

#### Find Your Project Path

**Via hPanel File Manager:**
1. Open File Manager
2. Navigate to your Laravel project root
3. Check the path in the address bar

**Via SSH:**
```bash
pwd
```

**Common Hostinger Paths:**
- Shared hosting: `/home/u123456789/public_html`
- VPS: `/home/username/public_html` or `/var/www/html`

### Option B: Continuous Queue Worker (VPS Only)

For VPS/dedicated servers, you can run a continuous worker.

#### Using Supervisor (Recommended for VPS)

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

#### Manual Continuous Worker (Not Recommended)

```bash
php artisan queue:work --daemon --tries=3
```

⚠️ **Warning**: This will stop if the SSH session closes. Use Supervisor instead.

## Step 4: Verify Queue is Working

### Check Jobs Table

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

Jobs should appear here when notifications are dispatched.

### Check Processed Jobs

After queue worker runs, jobs should be removed from `jobs` table.

### Check Failed Jobs

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

If jobs are failing, check the `exception` column for error details.

### Check Notifications Table

```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
```

Notifications should appear here after queue processing.

### Monitor Queue Worker

**With Logging:**
```bash
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --tries=3 >> /path/to/project/storage/logs/queue.log 2>&1
```

Then check logs:
```bash
tail -f storage/logs/queue.log
```

## Step 5: Testing on Production

### 1. Test Notification Dispatch

```bash
php artisan notifications:test order --order-id=1
```

### 2. Verify Job Created

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 1;
```

### 3. Wait for Queue Worker

Wait up to 1 minute for cron to run, or manually trigger:
```bash
php artisan queue:work --stop-when-empty
```

### 4. Verify Notification Sent

```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 1;
```

### 5. Check Email (if mail configured)

Verify email was sent to user's email address.

## Troubleshooting

### Issue: Jobs Not Processing

**Solution 1: Check Queue Connection**
```bash
php artisan config:clear
php artisan config:cache
```

**Solution 2: Verify Cron Job is Running**
- Check cron job logs in hPanel
- Add logging to cron command
- Verify cron job is active

**Solution 3: Check PHP Path**
```bash
which php
```

Use full path in cron:
```bash
* * * * * cd /path/to/project && /usr/bin/php artisan queue:work --stop-when-empty
```

### Issue: Jobs Failing

**Check Failed Jobs:**
```sql
SELECT exception FROM failed_jobs ORDER BY failed_at DESC LIMIT 1;
```

**Common Causes:**
1. **Model Serialization Issues**: Models in notifications must be serializable
2. **Missing Relationships**: Ensure relationships are loaded before queuing
3. **Mail Configuration**: Check `.env` mail settings
4. **Memory Limits**: Increase PHP memory limit

**Fix Serialization Issues:**

If you see errors about serialization, ensure models are fresh when notifications are processed:

```php
// In NotificationService, use fresh models
$order = Order::find($order->id); // Get fresh instance
$order->load('user'); // Load relationships
$order->user->notify(new OrderNotification($order, 'created'));
```

### Issue: Notifications Not Appearing

**Check:**
1. Queue worker is running
2. Jobs table has entries
3. No errors in `failed_jobs` table
4. User has `notifications` table entry
5. Mail configuration is correct

### Issue: Frontend URLs Not Working

**Verify:**
1. `FRONTEND_URL` is set in `.env`
2. URL format is correct: `{frontend_url}/{locale}/myorder/{id}`
3. Frontend route exists

**Test URL Generation:**
```php
$frontendUrl = config('app.frontend_url');
$locale = 'ar'; // or 'en'
$orderId = 1;
$url = rtrim($frontendUrl, '/') . '/' . $locale . '/myorder/' . $orderId;
echo $url;
```

## Monitoring

### Daily Checks

1. Check failed jobs count:
```sql
SELECT COUNT(*) FROM failed_jobs WHERE DATE(failed_at) = CURDATE();
```

2. Check pending jobs:
```sql
SELECT COUNT(*) FROM jobs;
```

3. Check recent notifications:
```sql
SELECT COUNT(*) FROM notifications WHERE DATE(created_at) = CURDATE();
```

### Set Up Alerts

Create a scheduled command to check for failed jobs:

```php
// In app/Console/Kernel.php
$schedule->call(function () {
    $failedCount = DB::table('failed_jobs')
        ->where('failed_at', '>', now()->subHour())
        ->count();
    
    if ($failedCount > 10) {
        // Send alert email
        Mail::to('admin@example.com')->send(new QueueAlert($failedCount));
    }
})->hourly();
```

## Best Practices

1. **Always use queues for notifications** - Improves response time
2. **Monitor failed jobs regularly** - Catch issues early
3. **Set appropriate retry limits** - Default is 3 tries
4. **Log queue activity** - Helps with debugging
5. **Test in staging first** - Verify before production
6. **Use database queue for shared hosting** - Most compatible
7. **Keep queue worker running** - Critical for notifications

## Configuration Summary

### .env Settings

```env
QUEUE_CONNECTION=database
FRONTEND_URL=https://your-frontend-domain.com
APP_URL=https://your-backend-domain.com
```

### Cron Job (Every Minute)

```bash
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

### Verification Commands

```bash
# Test notification
php artisan notifications:test order --order-id=1

# Check queue status
php artisan queue:work --stop-when-empty

# View logs
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log
```

## Support

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check queue logs: `storage/logs/queue.log`
3. Check failed jobs table
4. Test with `--sync` flag first
5. Verify cron job is active in hPanel
6. Contact Hostinger support if cron jobs not working

## Additional Resources

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Hostinger Cron Jobs Guide](https://www.hostinger.com/tutorials/how-to-set-up-cron-job)
- [Laravel Notification Documentation](https://laravel.com/docs/notifications)
