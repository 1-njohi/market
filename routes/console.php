<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Business Metrics Warmer
|--------------------------------------------------------------------------
| Runs at :55 past the hour so the cache is fresh when the dashboard
| is checked at the top of the hour. `withoutOverlapping` prevents a
| slow warm from stacking; `runInBackground` keeps the scheduler
| from blocking on the compute.
*/

Schedule::command('metrics:warm')
    ->hourlyAt(55)
    ->onOneServer()
    ->withoutOverlapping(15)
    ->runInBackground();

Schedule::command('health:check')
    ->everyFiveMinutes()
    ->withoutOverlapping(4)
    ->runInBackground();