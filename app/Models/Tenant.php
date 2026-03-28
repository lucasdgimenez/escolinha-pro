<?php

namespace App\Models;

use App\Observers\TenantObserver;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([TenantObserver::class])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'logo_path',
        'address',
        'city',
        'state',
        'phone',
        'primary_color',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
