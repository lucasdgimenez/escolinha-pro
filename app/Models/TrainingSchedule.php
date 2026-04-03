<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Models\Concerns\HasTenant;
use Database\Factories\TrainingScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSchedule extends Model
{
    /** @use HasFactory<TrainingScheduleFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'day_of_week',
        'start_time',
        'duration_minutes',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week'      => DayOfWeek::class,
            'is_active'        => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'schedule_id');
    }
}
