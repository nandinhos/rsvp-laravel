<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestContact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Expurgo de retenção (LGPD): para eventos ENCERRADOS há mais de
 * `retention_days`, anonimiza os dados pessoais dos convidados e elimina o
 * histórico de contatos. Anonimiza (em vez de excluir) para preservar a
 * integridade de contagens e a numeração estável (CA-13), sem reter PII.
 */
class RetentionPurger
{
    /**
     * @return array{events:int,guests:int,contacts:int} totais afetados
     */
    public function purge(?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        $events = Event::whereNotNull('closed_at')
            ->whereNotNull('retention_days')
            ->get()
            ->filter(fn (Event $e) => $e->closed_at->copy()->addDays($e->retention_days)->lessThan($now));

        $guestCount = 0;
        $contactCount = 0;

        foreach ($events as $event) {
            DB::transaction(function () use ($event, &$guestCount, &$contactCount) {
                $guests = $event->guests()->withTrashed()->get();

                foreach ($guests as $guest) {
                    $contactCount += GuestContact::where('guest_id', $guest->id)->delete();

                    $guest->forceFill([
                        'full_name' => 'Convidado anonimizado #'.$guest->number,
                        'note' => null,
                    ])->save();
                    $guestCount++;
                }
            });
        }

        return [
            'events' => $events->count(),
            'guests' => $guestCount,
            'contacts' => $contactCount,
        ];
    }
}
