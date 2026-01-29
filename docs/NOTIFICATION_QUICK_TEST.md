# Quick Notification Testing Guide

## Quick Start

### 1. Verify Setup

```bash
php artisan queue:verify
```

This checks:
- ✅ Queue configuration
- ✅ Database tables (jobs, failed_jobs, notifications)
- ✅ Frontend URL configuration
- ✅ Mail configuration
- ✅ Pending jobs status

### 2. Test Notifications

#### Test Order Notification
```bash
php artisan notifications:test order --order-id=1
```

#### Test Ticket Notification
```bash
php artisan notifications:test ticket --ticket-id=1
```

#### Test Payment Notification
```bash
php artisan notifications:test payment --order-id=1
```

#### Test Synchronously (Bypass Queue)
```bash
php artisan notifications:test order --order-id=1 --sync
```

### 3. Check Results

#### Check Jobs Table
```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;
```

#### Process Queue Manually
```bash
php artisan queue:work --stop-when-empty
```

#### Check Notifications
```sql
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5;
```

#### Check Failed Jobs
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;
```

## Hostinger Setup

### Cron Job (Every Minute)

In Hostinger hPanel → Advanced → Cron Jobs:

```bash
* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

Replace `/home/username/public_html` with your actual project path.

## Common Issues

### Jobs Not Processing
1. Check cron job is active in hPanel
2. Verify project path is correct
3. Check PHP path: `which php`
4. Add logging to cron for debugging

### Jobs Failing
1. Check `failed_jobs` table
2. Review exception message
3. Check mail configuration
4. Verify models exist when job processes

### Notifications Not Appearing
1. Verify queue worker is running
2. Check jobs are being processed
3. Verify user exists
4. Check mail configuration

## Full Documentation

See `docs/NOTIFICATION_QUEUE_SETUP.md` for complete setup guide.
