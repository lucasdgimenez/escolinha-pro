<?php

namespace App\Services\Evaluation;

use App\Jobs\GenerateEvaluationNarrative;
use App\Models\Evaluation;
use App\Models\EvaluationMetric;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    public function create(array $data, array $scores, User $coach): Evaluation
    {
        return DB::transaction(function () use ($data, $scores, $coach) {
            $player = Player::find($data['player_id']);

            $evaluation = Evaluation::create([
                'tenant_id'    => $coach->tenant_id,
                'player_id'    => $player->id,
                'coach_id'     => $coach->id,
                'category_id'  => $player->category_id,
                'evaluated_at' => $data['evaluated_at'],
                'notes'        => $data['notes'] ?? null,
            ]);

            foreach ($scores as $metricKeyId => $score) {
                EvaluationMetric::create([
                    'evaluation_id' => $evaluation->id,
                    'metric_key_id' => $metricKeyId,
                    'score'         => $score,
                ]);
            }

            GenerateEvaluationNarrative::dispatch($evaluation);

            return $evaluation;
        });
    }
}
