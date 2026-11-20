<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tiktok:send-purchases')->everyMinute()->withoutOverlapping();

Schedule::command('tiktok:generate-catalog')->hourly()->withoutOverlapping()
    ->when(fn () => (bool) config('tiktok.catalog_enabled'));

// Sync OTO shipment statuses every 15 minutes
Schedule::command('oto:sync-shipments')->everyFifteenMinutes();

// Fill metadata for imported legacy products without overwriting admin edits.
Schedule::command('seo:fill-missing --limit=40')->dailyAt('03:30')->withoutOverlapping();

// Check for delayed shipments daily at 9 AM
Schedule::command('oto:check-delayed')->dailyAt('09:00');
