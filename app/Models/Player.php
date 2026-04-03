<?php

namespace App\Models;

use App\Enums\DominantFoot;
use App\Enums\PlayerPosition;
use App\Models\Concerns\HasTenant;
use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'date_of_birth',
        'position',
        'dominant_foot',
        'photo_path',
        'guardian_name',
        'guardian_email',
        'guardian_phone',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'position'      => PlayerPosition::class,
            'dominant_foot' => DominantFoot::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
