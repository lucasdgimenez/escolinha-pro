<?php

namespace App\Jobs;

use App\Enums\NarrativeStatus;
use App\Models\Evaluation;
use App\Models\EvaluationNarrative;
use App\Services\Evaluation\EvaluationNarrativeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateEvaluationNarrative implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Evaluation $evaluation) {}

    public function handle(EvaluationNarrativeService $service): void
    {
        $narrative = EvaluationNarrative::firstOrCreate(
            ['evaluation_id' => $this->evaluation->id],
            ['status' => NarrativeStatus::Pending]
        );

        try {
            $text = $service->generate($this->evaluation);

            $narrative->update([
                'ai_generated_text' => $text,
                'status'            => NarrativeStatus::Generated,
                'generated_at'      => now(),
            ]);
        } catch (Throwable) {
            $narrative->update(['status' => NarrativeStatus::Failed]);
        }
    }
}
