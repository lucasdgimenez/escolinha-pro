<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationMetric extends Model
{
    protected $fillable = [
        'evaluation_id',
        'metric_key_id',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function metricKey(): BelongsTo
    {
        return $this->belongsTo(EvaluationMetricKey::class, 'metric_key_id');
    }
}
