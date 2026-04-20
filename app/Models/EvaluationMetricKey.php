<?php

namespace App\Models;

use App\Enums\MetricCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationMetricKey extends Model
{
    protected $fillable = [
        'name',
        'category',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'category'      => MetricCategory::class,
            'display_order' => 'integer',
        ];
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(EvaluationMetric::class, 'metric_key_id');
    }
}
