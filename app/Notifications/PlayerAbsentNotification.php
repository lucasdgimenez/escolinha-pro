<?php

namespace App\Notifications;

use App\Models\Player;
use App\Models\TrainingSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PlayerAbsentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Player $player,
        public readonly TrainingSession $session,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'player_id'    => $this->player->id,
            'player_name'  => $this->player->name,
            'session_id'   => $this->session->id,
            'session_date' => $this->session->session_date->format('d/m/Y'),
            'category'     => $this->session->category->name,
        ];
    }
}
