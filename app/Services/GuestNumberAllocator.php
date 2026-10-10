<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Aloca numeração sequencial de convidados por evento (PRD §2.1, CA-13).
 *
 * Usa incremento atômico sobre events.guest_number_seq dentro de uma transação,
 * garantindo numeração estável, monotônica e NÃO reutilizada mesmo sob
 * concorrência ou após exclusão lógica de convidados.
 */
class GuestNumberAllocator
{
    /**
     * Reserva e retorna o próximo número para o evento.
     * Deve ser chamado dentro de uma transação da operação de criação.
     */
    public function next(Event $event): int
    {
        return DB::transaction(function () use ($event) {
            // Lock pessimista na linha do evento: serializa alocações concorrentes.
            $current = DB::table('events')
                ->where('id', $event->id)
                ->lockForUpdate()
                ->value('guest_number_seq');

            $nextNumber = (int) $current + 1;

            DB::table('events')
                ->where('id', $event->id)
                ->update(['guest_number_seq' => $nextNumber]);

            return $nextNumber;
        });
    }
}
