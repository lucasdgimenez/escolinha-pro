<?php

namespace Database\Seeders;

use App\Enums\MetricCategory;
use App\Models\EvaluationMetricKey;
use Illuminate\Database\Seeder;

class EvaluationMetricKeysSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            [MetricCategory::Technical, 'Passe'],
            [MetricCategory::Technical, 'Drible'],
            [MetricCategory::Technical, 'Finalização'],
            [MetricCategory::Technical, 'Controle de bola'],
            [MetricCategory::Technical, 'Posicionamento'],
            [MetricCategory::Physical, 'Velocidade'],
            [MetricCategory::Physical, 'Resistência'],
            [MetricCategory::Physical, 'Força'],
            [MetricCategory::Physical, 'Agilidade'],
            [MetricCategory::Physical, 'Coordenação'],
            [MetricCategory::Tactical, 'Leitura de jogo'],
            [MetricCategory::Tactical, 'Pressão'],
            [MetricCategory::Tactical, 'Transição'],
            [MetricCategory::Tactical, 'Marcação'],
            [MetricCategory::Tactical, 'Cobertura'],
            [MetricCategory::Attitude, 'Disciplina'],
            [MetricCategory::Attitude, 'Comprometimento'],
            [MetricCategory::Attitude, 'Trabalho em equipe'],
            [MetricCategory::Attitude, 'Comunicação'],
            [MetricCategory::Attitude, 'Resiliência'],
        ];

        foreach ($keys as $order => [$category, $name]) {
            EvaluationMetricKey::firstOrCreate(
                ['name' => $name, 'category' => $category->value],
                ['display_order' => $order]
            );
        }
    }
}
