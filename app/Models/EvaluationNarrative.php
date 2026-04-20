<?php

namespace App\Models;

use App\Enums\NarrativeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationNarrative extends Model
{
    protected $fillable = [
        'evaluation_id',
        'ai_generated_text',
        'edited_text',
        'status',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => NarrativeStatus::class,
            'generated_at' => 'datetime',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
