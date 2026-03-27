<?php

namespace App\Services\Players;

use App\Enums\RoleSlug;
use App\Models\Category;
use App\Models\Player;
use App\Models\User;
use App\Services\Auth\InvitationService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlayerService
{
    public function __construct(private readonly InvitationService $invitationService) {}

    public function create(array $data, ?UploadedFile $photo, User $createdBy): Player
    {
        return DB::transaction(function () use ($data, $photo, $createdBy) {
            $age = intval(now()->year) - intval(Carbon::parse($data['date_of_birth'])->year);

            $category = Category::where('tenant_id', $createdBy->tenant_id)
                ->where('min_age', '<=', $age)
                ->where('max_age', '>=', $age)
                ->where('is_active', true)
                ->first();

            $photoPath = null;
            if ($photo) {
                $photoPath = $photo->store("players/{$createdBy->tenant_id}", 'public');
            }

            $player = Player::create([
                'tenant_id'      => $createdBy->tenant_id,
                'category_id'    => $category?->id,
                'name'           => $data['name'],
                'date_of_birth'  => $data['date_of_birth'],
                'position'       => $data['position'],
                'dominant_foot'  => $data['dominant_foot'],
                'photo_path'     => $photoPath,
                'guardian_name'  => $data['guardian_name'],
                'guardian_email' => $data['guardian_email'],
                'guardian_phone' => $data['guardian_phone'] ?? null,
            ]);

            try {
                $this->invitationService->invite($data['guardian_email'], RoleSlug::Parent, $createdBy);
            } catch (\InvalidArgumentException) {
                // Guardian already registered or invited — player is still created
            }

            return $player;
        });
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{created: int, errors: array<int, array{row: int, message: string}>}
     */
    public function importFromCsv(array $rows, User $createdBy): array
    {
        $created = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is the header

            $requiredFields = ['name', 'date_of_birth', 'position', 'dominant_foot', 'guardian_name', 'guardian_email'];
            $missing = array_filter($requiredFields, fn ($field) => empty($row[$field] ?? null));

            if ($missing) {
                $errors[] = [
                    'row'     => $rowNumber,
                    'message' => 'Campos obrigatórios ausentes: '.implode(', ', $missing),
                ];

                continue;
            }

            try {
                $this->create($row, null, $createdBy);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'     => $rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return compact('created', 'errors');
    }
}
