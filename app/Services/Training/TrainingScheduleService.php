<?php

namespace App\Services\Training;

use App\Enums\SessionStatus;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingScheduleService
{
    public function create(array $data, User $createdBy): TrainingSchedule
    {
        return TrainingSchedule::create(array_merge($data, [
            'tenant_id' => $createdBy->tenant_id,
        ]));
    }

    public function pause(TrainingSchedule $schedule): void
    {
        $schedule->update(['is_active' => false]);
    }

    public function resume(TrainingSchedule $schedule): void
    {
        $schedule->update(['is_active' => true]);
    }

    public function generateUpcomingSessions(int $daysAhead = 7): void
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays($daysAhead);

        TrainingSchedule::where('is_active', true)
            ->with('category')
            ->get()
            ->each(function (TrainingSchedule $schedule) use ($today, $until) {
                $targetDay = $schedule->day_of_week->carbonDay();

                $date = $today->copy()->next($targetDay);

                while ($date->lte($until)) {
                    DB::table('training_sessions')->insertOrIgnore([
                        'tenant_id'        => $schedule->tenant_id,
                        'category_id'      => $schedule->category_id,
                        'schedule_id'      => $schedule->id,
                        'session_date'     => $date->toDateString(),
                        'start_time'       => $schedule->start_time,
                        'duration_minutes' => $schedule->duration_minutes,
                        'location'         => $schedule->location,
                        'status'           => SessionStatus::Scheduled->value,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);

                    $date->addWeek();
                }
            });
    }

    public function createOneOff(array $data, User $createdBy): TrainingSession
    {
        return TrainingSession::create(array_merge($data, [
            'tenant_id'   => $createdBy->tenant_id,
            'schedule_id' => null,
            'status'      => SessionStatus::Scheduled->value,
        ]));
    }
}
