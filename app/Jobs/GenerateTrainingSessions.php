<?php

namespace App\Jobs;

use App\Services\Training\TrainingScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTrainingSessions implements ShouldQueue
{
    use Queueable;

    public function handle(TrainingScheduleService $service): void
    {
        $service->generateUpcomingSessions(7);
    }
}
