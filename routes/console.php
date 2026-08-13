<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('outreach:poll')->everyFifteenMinutes()->withoutOverlapping();

// Monday morning, so the week's follow-ups are read before the week starts.
Schedule::command('outreach:digest')->weeklyOn(1, '08:00');
