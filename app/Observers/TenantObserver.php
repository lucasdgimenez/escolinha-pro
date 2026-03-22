<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Tenant;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        $defaults = [
            ['name' => 'Sub-7',  'min_age' => 5,  'max_age' => 7],
            ['name' => 'Sub-9',  'min_age' => 8,  'max_age' => 9],
            ['name' => 'Sub-11', 'min_age' => 10, 'max_age' => 11],
            ['name' => 'Sub-13', 'min_age' => 12, 'max_age' => 13],
            ['name' => 'Sub-15', 'min_age' => 14, 'max_age' => 15],
            ['name' => 'Sub-17', 'min_age' => 16, 'max_age' => 17],
        ];

        foreach ($defaults as $category) {
            Category::create(array_merge($category, [
                'tenant_id' => $tenant->id,
                'monthly_fee' => 0,
            ]));
        }
    }
}
