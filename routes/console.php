<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync OTO shipment statuses every 15 minutes
Schedule::command('oto:sync-shipments')->everyFifteenMinutes();

// Check for delayed shipments daily at 9 AM
Schedule::command('oto:check-delayed')->dailyAt('09:00');
