<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily Resource Usage Check at 6:00 AM
Schedule::command('resource:check')
    ->dailyAt('06.00')
    ->name('Daily Resource Usage Check')
    ->withoutOverlapping()
    ->runInBackground();
