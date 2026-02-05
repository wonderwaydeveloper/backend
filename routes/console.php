<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule token cleanup job
Schedule::job(\App\Jobs\TokenCleanupJob::class)
    ->hourly()
    ->withoutOverlapping();

// Schedule token cleanup command as backup
Schedule::command('auth:cleanup-tokens')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping();

// Schedule audit logs cleanup (keep 90 days)
Schedule::command('audit:cleanup --days=90')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->withoutOverlapping();
