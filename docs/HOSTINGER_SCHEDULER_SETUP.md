# Setting Up Laravel Scheduler on Hostinger

This guide explains how to configure Laravel's task scheduler on Hostinger hosting.

## Prerequisites

- Laravel application deployed on Hostinger
- Access to Hostinger hPanel (control panel)
- SSH access (for VPS) or Cron Jobs section (for shared hosting)

## Method 1: Using Hostinger hPanel (Shared Hosting)

### Step 1: Access Cron Jobs

1. Log in to your **Hostinger hPanel**
2. Navigate to **Advanced** → **Cron Jobs**
3. Click **Create Cron Job**

### Step 2: Configure Cron Job

**Cron Command:**
```bash
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

**Important**: Replace `/home/username/public_html` with your actual project path.

**To find your project path:**
- Check your domain's document root in hPanel
- Or use: `pwd` command via SSH/File Manager

**Example paths:**
- Shared hosting: `/home/u123456789/public_html`
- VPS: `/home/yourusername/public_html` or `/var/www/html`

### Step 3: Set Schedule Frequency

- **Minute**: `*` (every minute)
- **Hour**: `*` (every hour)
- **Day**: `*` (every day)
- **Month**: `*` (every month)
- **Weekday**: `*` (every weekday)

### Step 4: Save Cron Job

Click **Create** or **Save** to activate the cron job.

## Method 2: Using SSH (VPS/Dedicated Server)

### Step 1: Access via SSH

```bash
ssh username@your-server-ip
```

### Step 2: Edit Crontab

```bash
crontab -e
```

### Step 3: Add Laravel Scheduler

Add this line:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Example:**
```bash
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Step 4: Save and Exit

- Press `Esc`
- Type `:wq` (for vi/vim) or `Ctrl+X` then `Y` (for nano)
- Press `Enter`

### Step 5: Verify Cron Job

```bash
crontab -l
```

You should see your cron job listed.

## Method 3: Using .htaccess (Alternative - Not Recommended)

If cron jobs are not available, you can use a workaround with `.htaccess`:

**Create/Edit `.htaccess` in public folder:**

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/schedule-run$
    RewriteRule ^(.*)$ /schedule-run.php [L]
</IfModule>
```

**Create `public/schedule-run.php`:**

```php
<?php
// Only allow from localhost or specific IP
$allowedIPs = ['127.0.0.1', '::1', 'YOUR_SERVER_IP'];
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
    die('Access denied');
}

// Run scheduler
$artisan = __DIR__ . '/../artisan';
exec("php {$artisan} schedule:run >> /dev/null 2>&1");
echo "Scheduler executed at " . date('Y-m-d H:i:s');
```

**Then set up a cron job to call:**
```bash
curl https://yourdomain.com/schedule-run
```

⚠️ **Note**: This method is less secure and not recommended for production.

## Finding Your Project Path

### Via hPanel File Manager

1. Open **File Manager** in hPanel
2. Navigate to your Laravel project root
3. Check the path shown in the address bar

### Via SSH

```bash
pwd
```

### Common Hostinger Paths

- **Shared Hosting**: `/home/u123456789/public_html` or `/home/u123456789/domains/yourdomain.com/public_html`
- **VPS**: `/home/username/public_html` or `/var/www/html`
- **WordPress**: Usually `/public_html` or `/public_html/wp-content`

## Testing the Scheduler

### Test Manually

```bash
php artisan schedule:run
```

### Check Scheduled Commands

```bash
php artisan schedule:list
```

### View Logs

Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

### Test Specific Command

```bash
php artisan product-views:send-offers --hours=1 --dry-run
```

## Troubleshooting

### Issue: Cron Job Not Running

**Solution 1: Check PHP Path**
```bash
which php
```

Use full path in cron:
```bash
* * * * * cd /path/to/project && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Solution 2: Check Permissions**
```bash
chmod +x artisan
```

**Solution 3: Add Logging**
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /path/to/project/storage/logs/cron.log 2>&1
```

### Issue: Permission Denied

```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Issue: Commands Not Found

Use full paths:
```bash
* * * * * cd /home/username/public_html && /usr/bin/php /home/username/public_html/artisan schedule:run >> /dev/null 2>&1
```

### Issue: Queue Workers Not Running

If your scheduled commands dispatch jobs, ensure queue workers are running:

```bash
php artisan queue:work --daemon
```

Or set up a separate cron for queue worker:
```bash
* * * * * cd /path/to/project && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

## Hostinger-Specific Configuration

### Shared Hosting Limitations

- Limited cron job frequency (usually minimum 1 minute)
- May have resource limits
- PHP version might be different from CLI

### VPS Advantages

- Full control over cron jobs
- Can run queue workers as daemons
- Better performance

### Recommended Setup for Hostinger

1. **Set up main scheduler cron** (every minute)
2. **Set up queue worker** (if using queues)
3. **Monitor logs** regularly
4. **Use Supervisor** (VPS only) for queue workers

## Supervisor Setup (VPS Only)

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
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

## Verification Checklist

- [ ] Cron job created in hPanel or via SSH
- [ ] Correct project path used
- [ ] PHP path is correct
- [ ] Permissions set correctly
- [ ] `schedule:run` executes successfully
- [ ] Commands appear in `schedule:list`
- [ ] Logs show scheduled tasks running
- [ ] Queue workers running (if needed)

## Support

If you encounter issues:

1. Check Hostinger documentation
2. Contact Hostinger support
3. Check Laravel logs: `storage/logs/laravel.log`
4. Test commands manually first
5. Verify PHP version matches: `php -v`

## Additional Resources

- [Laravel Task Scheduling](https://laravel.com/docs/scheduling)
- [Hostinger Knowledge Base](https://www.hostinger.com/tutorials)
- [Cron Job Generator](https://crontab.guru/)
