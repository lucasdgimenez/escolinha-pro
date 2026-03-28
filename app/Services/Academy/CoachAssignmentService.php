<?php

namespace App\Services\Academy;

use App\Enums\RoleSlug;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class CoachAssignmentService
{
    public function syncCategories(User $coach, array $categoryIds): void
    {
        if (! $coach->hasRole(RoleSlug::Coach)) {
            throw new \InvalidArgumentException('O usuário selecionado não é um treinador.');
        }

        $coach->assignedCategories()->sync($categoryIds);
    }

    public function getCoachesWithCategories(Tenant $tenant): Collection
    {
        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereHas('role', fn ($q) => $q->where('slug', RoleSlug::Coach->value))
            ->with('assignedCategories')
            ->orderBy('name')
            ->get();
    }
}
