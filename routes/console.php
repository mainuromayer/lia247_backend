<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();



// Schedule::command('etsy:sync --keywords=gift --limit=10')
//     ->cron('30 2 */5 * *') 
//     ->withoutOverlapping()
//     ->appendOutputTo(storage_path('logs/etsy-sync.log'));

Schedule::command('etsy:sync --keywords=gift --limit=10')
    ->twiceDaily(2, 14)
    // ->everySecond(function () {
    //     Artisan::call('etsy:sync --keywords=gift --limit=10');
    // })
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/etsy-sync.log'));


// ->dailyAt('02:00')
// ->dailyAt('14:00')