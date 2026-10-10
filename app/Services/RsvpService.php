<?php

namespace App\Services;

use App\Enums\ParticipationRole;
use App\Enums\RelationshipType;
use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\GuestRelationship;
use Illuminate\Support\Facades\DB;

/**
 * Máquina de estados do RSVP e regras de representação (PRD §2.3–§2.5).
 *
 * Princípios:
 *  - Os três estados são distintos; recusar é SEMPRE ação explícita (CA-03).
 *  - Confirmar um acompanhante de titular não confirmado o promove a
 *    representante, preservando a origem como acompanhante (CA-05).
 *  - A confirmação posterior do titular representado NÃO altera registros
 *    silenciosamente; cria uma pendência de conflito bloqueante (CA-06).
 */
class RsvpService
{
    /**
     * Confirma a participação de um convidado, aplicando as regras de
     * promoção e detecção de conflito conforme o papel/vínculo.
     */
    public function confirm(Guest $guest): Guest
    {
        return DB::transaction(function () use ($guest) {
            $guest->forceFill(['rsvp_status' => RsvpStatus::Confirmado])->save();

            // Regra de promoção (CA-05): acompanhante de titular não confirmado
            // que confirma vira representante, preservando origem.
            $this->promoteCompanionIfNeeded($guest);

            // Detecção de conflito (CA-06): se este convidado é um titular que
            // acabou de confirmar e possui representante ativo confirmado.
            $this->flagConflictIfRepresentedHostConfirms($guest);

            return $guest->refresh();
        });
    }

    /**
     * Registra recusa (não participará). Ação explícita (CA-03).
     */
    public function decline(Guest $guest): Guest
    {
        $guest->forceFill(['rsvp_status' => RsvpStatus::NaoParticipara])->save();

        return $guest->refresh();
    }

    /**
     * Retorna o convidado ao estado pendente. Ação explícita.
     */
    public function reset(Guest $guest): Guest
    {
        $guest->forceFill(['rsvp_status' => RsvpStatus::Pendente])->save();

        return $guest->refresh();
    }

    /**
     * Se o convidado é acompanhante de um titular não confirmado, promove-o a
     * representante mantendo origin_type = acompanhante (CA-05).
     */
    private function promoteCompanionIfNeeded(Guest $guest): void
    {
        /** @var GuestRelationship|null $link */
        $link = $guest->hostRelationship()->with('relatedGuest')->first();

        if ($link === null || $link->type !== RelationshipType::Acompanhante) {
            return;
        }

        $host = $link->relatedGuest;

        if ($host === null || $host->rsvp_status === RsvpStatus::Confirmado) {
            return; // titular confirmado: permanece acompanhante (CA-04)
        }

        // Promoção: muda o tipo ATUAL para representante, preserva a origem.
        $link->forceFill(['type' => RelationshipType::Representante])->save();
        $guest->forceFill(['participation_role' => ParticipationRole::Representante])->save();
    }

    /**
     * Se o convidado recém-confirmado é titular representado por alguém que
     * já confirmou, marca o vínculo como conflito bloqueante (CA-06).
     * Nada é removido ou sobrescrito silenciosamente.
     */
    private function flagConflictIfRepresentedHostConfirms(Guest $guest): void
    {
        $activeRepresentations = $guest->dependents()
            ->where('type', RelationshipType::Representante->value)
            ->whereNull('resolved_at')
            ->with('guest')
            ->get();

        foreach ($activeRepresentations as $representation) {
            $representative = $representation->guest;

            if ($representative !== null && $representative->rsvp_status === RsvpStatus::Confirmado) {
                $representation->forceFill(['is_conflict' => true])->save();
            }
        }
    }
}
