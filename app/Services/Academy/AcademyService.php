<?php

namespace App\Services\Academy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class AcademyService
{
    public function updateProfile(Tenant $tenant, array $data, $logo = null): Tenant
    {
        if ($logo) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $data['logo_path'] = $logo->store("tenants/{$tenant->id}", 'public');
        }

        $tenant->update($data);

        return $tenant;
    }
}
