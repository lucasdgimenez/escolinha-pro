<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'slug' => RoleSlug::class,
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
