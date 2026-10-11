<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\RsvpStatus;
use App\Exceptions\GuestDomainException;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Credenciamento (check-in) de convidados (PRD §2.6, CA-09).
 *
 * Idempotente: check-ins repetidos NÃO produzem presenças duplicadas nem
 * sobrescrevem o horário/responsável do primeiro credenciamento.
 *
 * Regra de aptidão: somente convidados com RSVP confirmado podem ser
 * credenciados — impede o estado inconsistente "pendente + presente".
 */
class CheckInService
{
    /**
     * Credencia o convidado. Se já estiver presente, não altera nada (CA-09).
     * Seguro sob concorrência via lock pessimista na linha (CA-13).
     *
     * @throws GuestDomainException se o convidado não estiver confirmado.
     */
    public function checkIn(Guest $guest, User $operator): Guest
    {
        return DB::transaction(function () use ($guest, $operator) {
            /** @var Guest $locked */
            $locked = Guest::whereKey($guest->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->attendance_status === AttendanceStatus::Presente) {
                return $locked; // já credenciado: idempotente
            }

            if ($locked->rsvp_status !== RsvpStatus::Confirmado) {
                throw new GuestDomainException(
                    'Apenas convidados confirmados podem ser credenciados. Confirme o RSVP primeiro.'
                );
            }

            $locked->forceFill([
                'attendance_status' => AttendanceStatus::Presente,
                'checked_in_at' => now(),
                'checked_in_by' => $operator->id,
            ])->save();

            return $locked;
        });
    }

    /**
     * Reverte o credenciamento (correção, PRD §2.6).
     */
    public function undo(Guest $guest): Guest
    {
        $guest->forceFill([
            'attendance_status' => AttendanceStatus::Aguardando,
            'checked_in_at' => null,
            'checked_in_by' => null,
        ])->save();

        return $guest->refresh();
    }
}
