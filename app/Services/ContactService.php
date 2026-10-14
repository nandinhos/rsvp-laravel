<?php

namespace App\Services;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registro de contatos de confirmação ativa (M1).
 *
 * Append-only: cada chamada cria um novo registro, preservando o histórico.
 * Quando o resultado reflete uma decisão de RSVP (confirmou/reconfirmado ou
 * recusou), o estado do convidado é espelhado reusando o RsvpService — o
 * contato é a origem, o RSVP é a consequência, sem alterar o enum de domínio.
 */
class ContactService
{
    public function __construct(private readonly RsvpService $rsvp) {}

    /**
     * @param  array{channel:ContactChannel,outcome:ContactOutcome,scheduled_return_at?:?string,note?:?string}  $data
     */
    public function record(Guest $guest, User $operator, array $data): GuestContact
    {
        return DB::transaction(function () use ($guest, $operator, $data) {
            /** @var ContactOutcome $outcome */
            $outcome = $data['outcome'];

            $contact = GuestContact::create([
                'event_id' => $guest->event_id,
                'guest_id' => $guest->id,
                'channel' => $data['channel'],
                'outcome' => $outcome,
                'scheduled_return_at' => $data['scheduled_return_at'] ?? null,
                'note' => $data['note'] ?? null,
                'operator_id' => $operator->id,
            ]);

            // Espelha a decisão no RSVP do convidado quando aplicável.
            if ($outcome->reflectsConfirmation()) {
                $this->rsvp->confirm($guest);
            } elseif ($outcome->reflectsDecline()) {
                $this->rsvp->decline($guest);
            }

            return $contact;
        });
    }
}
