<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Evaluation extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'player_id',
        'coach_id',
        'category_id',
        'evaluated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'evaluated_at' => 'date',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(EvaluationMetric::class);
    }

    public function narrative(): HasOne
    {
        return $this->hasOne(EvaluationNarrative::class);
    }
}
