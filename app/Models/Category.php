<?php

namespace App\Models;

use App\Models\Concerns\HasTenant;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTenant, HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'min_age',
        'max_age',
        'monthly_fee',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'monthly_fee' => 'decimal:2',
            'min_age'     => 'integer',
            'max_age'     => 'integer',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
