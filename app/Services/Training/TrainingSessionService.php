<?php

namespace App\Services\Training;

use App\Enums\SessionStatus;
use App\Models\TrainingSession;
use InvalidArgumentException;

class TrainingSessionService
{
    /** @return array<string, SessionStatus[]> */
    private function allowedTransitions(): array
    {
        return [
            SessionStatus::Scheduled->value  => [SessionStatus::InProgress, SessionStatus::Cancelled],
            SessionStatus::InProgress->value => [SessionStatus::Completed, SessionStatus::Cancelled],
        ];
    }

    public function canTransitionTo(TrainingSession $session, SessionStatus $target): bool
    {
        $allowed = $this->allowedTransitions()[$session->status->value] ?? [];

        return in_array($target, $allowed, strict: true);
    }

    public function transitionTo(TrainingSession $session, SessionStatus $target): void
    {
        if (! $this->canTransitionTo($session, $target)) {
            throw new InvalidArgumentException(
                "Não é possível alterar o status de {$session->status->label()} para {$target->label()}."
            );
        }

        $session->update(['status' => $target]);
    }
}
