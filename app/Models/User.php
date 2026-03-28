<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Models\Concerns\HasTenant;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTenant, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'coach_category', 'coach_id', 'category_id')->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role->slug === RoleSlug::SuperAdmin;
    }

    public function hasRole(RoleSlug $role): bool
    {
        return $this->role->slug === $role;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
