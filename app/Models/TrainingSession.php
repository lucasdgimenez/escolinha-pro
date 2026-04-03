<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\HasTenant;
use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'schedule_id',
        'session_date',
        'start_time',
        'duration_minutes',
        'location',
        'status',
        'notes',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'session_date'     => 'date',
            'status'           => SessionStatus::class,
            'duration_minutes' => 'integer',
            'rating'           => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TrainingSchedule::class, 'schedule_id');
    }
}
