<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


/*
|--------------------------------------------------------------------------
| Sync + Settlement Schedule
|--------------------------------------------------------------------------
|
| Four independent commands. Each runs on its own cadence so a failure in
| one layer never cascades into the others:
|
|   fixtures:sync-upcoming   daily      pull fixtures for the next 14 days
|   odds:sync-upcoming       hourly     refresh odds for NS fixtures in <48h
|   fixtures:sync-live       every 3m   poll live scores / status
|   fixtures:settle          every 2m   resolve odds from local data only
|
| withoutOverlapping() prevents a slow run from stacking on the next tick.
| onOneServer() prevents parallel execution across horizontally scaled app
| instances — requires a shared cache driver (redis or database).
|
*/

Schedule::command('fixtures:sync-upcoming')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('odds:sync-upcoming')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('fixtures:sync-live')
    ->everyThreeMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('fixtures:settle')
    ->everyTwoMinutes()
    ->withoutOverlapping()
    ->onOneServer();
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

