<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Autorização por evento (PRD §8, CA-01).
 *
 * Toda decisão depende do papel do usuário NAQUELE evento (EventUser).
 * Um usuário sem vínculo com o evento é tratado como não autorizado —
 * garantindo isolamento: não consulta, altera, importa ou exporta dados
 * de eventos aos quais não pertence.
 */
class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $user->belongsToEvent($event);
    }

    public function manageGuests(User $user, Event $event): bool
    {
        return (bool) $user->roleFor($event)?->canManageGuests();
    }

    public function operate(User $user, Event $event): bool
    {
        return (bool) $user->roleFor($event)?->canOperate();
    }

    public function export(User $user, Event $event): bool
    {
        return (bool) $user->roleFor($event)?->canExport();
    }

    public function administer(User $user, Event $event): bool
    {
        return (bool) $user->roleFor($event)?->canAdminister();
    }

    public function update(User $user, Event $event): bool
    {
        return $this->administer($user, $event);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->administer($user, $event);
    }
}
