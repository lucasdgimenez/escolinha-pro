<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TrainingSchedule;
use App\Models\TrainingSession;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'min_age',
        'max_age',
        'monthly_fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'monthly_fee' => 'decimal:2',
            'min_age'     => 'integer',
            'max_age'     => 'integer',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coach_category', 'category_id', 'coach_id')->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TrainingSchedule::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }
}
