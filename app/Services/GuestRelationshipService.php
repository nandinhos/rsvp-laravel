<?php

namespace App\Services;

use App\Enums\ParticipationRole;
use App\Enums\RelationshipType;
use App\Exceptions\GuestDomainException;
use App\Models\Guest;
use App\Models\GuestRelationship;
use Illuminate\Support\Facades\DB;

/**
 * Gerencia vínculos de acompanhamento e representação entre convidados
 * (PRD §2.4 e §2.5), aplicando as invariantes estruturais:
 *  - não representar a si mesmo;
 *  - sem substituição circular;
 *  - titular e dependente no mesmo evento;
 *  - um dependente possui no máximo um vínculo ativo.
 */
class GuestRelationshipService
{
    /**
     * Cria um vínculo de acompanhante: $companion acompanha $host.
     * O papel de participação do acompanhante é ajustado para Acompanhante.
     */
    public function attachCompanion(Guest $companion, Guest $host): GuestRelationship
    {
        return $this->attach($companion, $host, RelationshipType::Acompanhante);
    }

    /**
     * Cria um vínculo de representante: $representative representa $host.
     */
    public function attachRepresentative(Guest $representative, Guest $host): GuestRelationship
    {
        return $this->attach($representative, $host, RelationshipType::Representante);
    }

    private function attach(Guest $dependent, Guest $host, RelationshipType $type): GuestRelationship
    {
        $this->guardSameEvent($dependent, $host);
        $this->guardNotSelf($dependent, $host);
        $this->guardNoCycle($dependent, $host);
        $this->guardSingleActiveLink($dependent);
        $this->guardSingleActiveRepresentative($host, $type);

        return DB::transaction(function () use ($dependent, $host, $type) {
            $relationship = GuestRelationship::create([
                'event_id' => $dependent->event_id,
                'guest_id' => $dependent->id,
                'related_guest_id' => $host->id,
                'type' => $type,
                'origin_type' => $type,
                'is_conflict' => false,
            ]);

            $role = $type === RelationshipType::Representante
                ? ParticipationRole::Representante
                : ParticipationRole::Acompanhante;

            $dependent->forceFill(['participation_role' => $role])->save();

            return $relationship;
        });
    }

    private function guardSameEvent(Guest $dependent, Guest $host): void
    {
        if ($dependent->event_id !== $host->event_id) {
            throw new GuestDomainException('Vínculo só é permitido entre convidados do mesmo evento.');
        }
    }

    private function guardNotSelf(Guest $dependent, Guest $host): void
    {
        if ($dependent->id === $host->id) {
            throw new GuestDomainException('Um convidado não pode representar ou acompanhar a si mesmo.');
        }
    }

    /**
     * Impede substituição circular: o titular (host) não pode, direta ou
     * indiretamente, depender do próprio dependente.
     */
    private function guardNoCycle(Guest $dependent, Guest $host): void
    {
        $visited = [];
        $currentId = $host->id;

        while ($currentId !== null) {
            if ($currentId === $dependent->id) {
                throw new GuestDomainException('Vínculo criaria uma substituição circular.');
            }

            if (in_array($currentId, $visited, true)) {
                break; // proteção extra contra laço inesperado
            }
            $visited[] = $currentId;

            $currentId = GuestRelationship::where('guest_id', $currentId)->value('related_guest_id');
        }
    }

    private function guardSingleActiveLink(Guest $dependent): void
    {
        $exists = GuestRelationship::where('guest_id', $dependent->id)->exists();

        if ($exists) {
            throw new GuestDomainException('Este convidado já possui um vínculo ativo.');
        }
    }

    /**
     * Um titular não deve possuir múltiplos representantes ativos sem
     * resolução explícita (PRD §2.5). A guarda só se aplica a vínculos de
     * representação; acompanhantes múltiplos são permitidos.
     */
    private function guardSingleActiveRepresentative(Guest $host, RelationshipType $type): void
    {
        if ($type !== RelationshipType::Representante) {
            return;
        }

        $exists = GuestRelationship::where('related_guest_id', $host->id)
            ->where('type', RelationshipType::Representante->value)
            ->whereNull('resolved_at')
            ->exists();

        if ($exists) {
            throw new GuestDomainException('Este titular já possui um representante ativo. Resolva antes de adicionar outro.');
        }
    }
}
