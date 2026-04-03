<?php

use App\Jobs\GenerateTrainingSessions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(GenerateTrainingSessions::class)->weekly()->sundays()->at('01:00');
