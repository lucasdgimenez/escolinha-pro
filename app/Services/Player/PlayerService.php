<?php

namespace App\Services\Player;

use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Player;
use App\Models\User;
use App\Services\Auth\InvitationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlayerService
{
    public function __construct(private readonly InvitationService $invitationService) {}

    public function create(array $data, $photo = null, User $invitedBy): Player
    {
        return DB::transaction(function () use ($data, $photo, $invitedBy) {
            if ($photo) {
                $data['photo_path'] = $photo->store("players/{$invitedBy->tenant_id}", 'public');
            }

            $player = Player::create($data);

            $this->assignCategory($player);

            try {
                $this->invitationService->invite($player->guardian_email, RoleSlug::Parent, $invitedBy);
            } catch (\Throwable) {
                // Invitation failure must not abort player creation
            }

            return $player;
        });
    }

    public function assignCategory(Player $player): void
    {
        $age = now()->year - $player->date_of_birth->year;

        $category = Category::where('is_active', true)
            ->where('min_age', '<=', $age)
            ->where('max_age', '>=', $age)
            ->first();

        if ($category) {
            $player->category_id = $category->id;
            $player->save();
        }
    }

    /**
     * @return array{imported: int, errors: array<int, array{row: int, message: string}>}
     */
    public function importFromCsv(UploadedFile $file, User $importedBy): array
    {
        $imported = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        $row = 0;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;

            // Skip header row
            if ($row === 1) {
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($line))) {
                continue;
            }

            [$name, $dateOfBirth, $position, $dominantFoot, $guardianName, $guardianEmail, $guardianPhone] = array_pad($line, 7, null);

            $validationError = $this->validateCsvRow($row, $name, $dateOfBirth, $position, $dominantFoot, $guardianName, $guardianEmail);

            if ($validationError) {
                $errors[] = $validationError;
                continue;
            }

            try {
                $this->create([
                    'name' => trim($name),
                    'date_of_birth' => trim($dateOfBirth),
                    'position' => trim($position),
                    'dominant_foot' => trim($dominantFoot),
                    'guardian_name' => trim($guardianName),
                    'guardian_email' => trim($guardianEmail),
                    'guardian_phone' => $guardianPhone ? trim($guardianPhone) : null,
                ], null, $importedBy);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = ['row' => $row, 'message' => "Linha {$row}: {$e->getMessage()}"];
            }
        }

        fclose($handle);

        return ['imported' => $imported, 'errors' => $errors];
    }

    public function csvTemplateContent(): string
    {
        return "Nome,Data de Nascimento,Posição,Pé Dominante,Nome do Responsável,Email do Responsável,Telefone do Responsável\n";
    }

    /**
     * @return array{row: int, message: string}|null
     */
    private function validateCsvRow(int $row, ?string $name, ?string $dateOfBirth, ?string $position, ?string $dominantFoot, ?string $guardianName, ?string $guardianEmail): ?array
    {
        if (empty(trim($name ?? ''))) {
            return ['row' => $row, 'message' => "Linha {$row}: o nome do atleta é obrigatório."];
        }

        if (empty(trim($dateOfBirth ?? '')) || ! strtotime($dateOfBirth)) {
            return ['row' => $row, 'message' => "Linha {$row}: data de nascimento inválida."];
        }

        if (empty(trim($position ?? ''))) {
            return ['row' => $row, 'message' => "Linha {$row}: a posição é obrigatória."];
        }

        if (empty(trim($dominantFoot ?? ''))) {
            return ['row' => $row, 'message' => "Linha {$row}: o pé dominante é obrigatório."];
        }

        if (empty(trim($guardianName ?? ''))) {
            return ['row' => $row, 'message' => "Linha {$row}: o nome do responsável é obrigatório."];
        }

        if (empty(trim($guardianEmail ?? '')) || ! filter_var(trim($guardianEmail), FILTER_VALIDATE_EMAIL)) {
            return ['row' => $row, 'message' => "Linha {$row}: e-mail do responsável inválido."];
        }

        return null;
    }
}
