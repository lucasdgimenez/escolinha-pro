<?php

namespace App\Services\Auth;

use App\Enums\RoleSlug;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InvitationService
{
    public function invite(string $email, RoleSlug $role, User $invitedBy): Invitation
    {
        if (User::withoutGlobalScopes()->where('email', $email)->exists()) {
            throw new \InvalidArgumentException('Este e-mail já está cadastrado.');
        }

        Invitation::where('email', $email)->whereNull('accepted_at')->delete();

        $roleModel = Role::where('slug', $role->value)->firstOrFail();

        $invitation = Invitation::create([
            'tenant_id' => $invitedBy->tenant_id,
            'invited_by' => $invitedBy->id,
            'email' => $email,
            'role_id' => $roleModel->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        Notification::route('mail', $email)->notify(new InvitationNotification($invitation));

        return $invitation;
    }

    public function resend(Invitation $invitation): void
    {
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        Notification::route('mail', $invitation->email)->notify(new InvitationNotification($invitation));
    }

    public function accept(Invitation $invitation, string $name, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::create([
                'tenant_id' => $invitation->tenant_id,
                'role_id' => $invitation->role_id,
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });
    }
}
