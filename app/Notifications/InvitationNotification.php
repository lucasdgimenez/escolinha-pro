<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('invitation.accept', ['token' => $this->invitation->token], false));

        return (new MailMessage)
            ->subject('Você foi convidado para o '.config('app.name'))
            ->greeting('Olá!')
            ->line('Você foi convidado para se cadastrar como '.$this->invitation->role->name.'.')
            ->action('Aceitar convite', $url)
            ->line('Este convite expira em 72 horas.')
            ->line('Se você não esperava este convite, pode ignorar este e-mail.')
            ->salutation('Atenciosamente, '.config('app.name'));
    }
}
