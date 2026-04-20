<?php

namespace App\Services\Evaluation;

use App\Models\Evaluation;
use OpenAI;

class EvaluationNarrativeService
{
    public function generate(Evaluation $evaluation): string
    {
        $evaluation->load(['player', 'category', 'metrics.metricKey']);

        $scoreLines = $evaluation->metrics
            ->sortBy('metricKey.display_order')
            ->groupBy(fn ($m) => $m->metricKey->category->label())
            ->map(fn ($group, $categoryLabel) => $categoryLabel.': '.
                $group->map(fn ($m) => $m->metricKey->name.' ('.$m->score.'/10)')->implode(', ')
            )
            ->values()
            ->implode("\n");

        $prompt = <<<EOT
Você é um assistente técnico de futebol. Escreva uma narrativa de avaliação individual em português brasileiro para o atleta abaixo. Seja objetivo, construtivo e use linguagem adequada para o contexto esportivo. Destaque pontos fortes e áreas de melhoria com base nas notas.

Atleta: {$evaluation->player->name}
Categoria: {$evaluation->category->name}
Data: {$evaluation->evaluated_at->format('d/m/Y')}

Notas (escala 1–10):
{$scoreLines}

{$this->notesContext($evaluation)}
Escreva um parágrafo de 3 a 5 frases.
EOT;

        $client = OpenAI::factory()
            ->withApiKey(config('services.openai.api_key'))
            ->make();

        $response = $client->chat()->create([
            'model'    => config('services.openai.model', 'gpt-4o-mini'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    private function notesContext(Evaluation $evaluation): string
    {
        if (! $evaluation->notes) {
            return '';
        }

        return "Observações do coach: {$evaluation->notes}\n\n";
    }
}
