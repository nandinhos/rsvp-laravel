<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use App\Models\Event;

/**
 * Contagem de indicadores de participação (PRD §4).
 *
 * Garante que cada pessoa é contada UMA única vez, pelo seu papel ATUAL
 * (CA-07). Um acompanhante promovido a representante conta apenas em
 * representantes. A classificação usa participation_role, que o RsvpService
 * mantém consistente com o vínculo.
 */
class GuestMetrics
{
    public function __construct(private readonly Event $event) {}

    /** Titulares confirmados (papel = titular). */
    public function confirmedHosts(): int
    {
        return $this->baseConfirmed()
            ->where('participation_role', ParticipationRole::Titular->value)
            ->count();
    }

    /** Acompanhantes confirmados (papel atual = acompanhante). */
    public function confirmedCompanions(): int
    {
        return $this->baseConfirmed()
            ->where('participation_role', ParticipationRole::Acompanhante->value)
            ->count();
    }

    /** Representantes confirmados (papel atual = representante). */
    public function confirmedRepresentatives(): int
    {
        return $this->baseConfirmed()
            ->where('participation_role', ParticipationRole::Representante->value)
            ->count();
    }

    /** Pendentes ou que informaram não participar (PRD §4). */
    public function notConfirmed(): int
    {
        return $this->event->guests()
            ->whereIn('rsvp_status', [
                RsvpStatus::Pendente->value,
                RsvpStatus::NaoParticipara->value,
            ])
            ->count();
    }

    /** Pessoas efetivamente credenciadas. */
    public function present(): int
    {
        return $this->event->guests()
            ->where('attendance_status', AttendanceStatus::Presente->value)
            ->count();
    }

    /**
     * Total de participantes previstos = titulares + acompanhantes +
     * representantes confirmados. Sem dupla contagem (CA-07).
     */
    public function expectedParticipants(): int
    {
        return $this->baseConfirmed()->count();
    }

    public function estimatedAudience(): int
    {
        return (int) $this->event->estimated_audience;
    }

    /** Taxa de confirmação; null quando público estimado é zero (sem divisão por zero). */
    public function confirmationRate(): ?float
    {
        $estimated = $this->estimatedAudience();

        return $estimated > 0
            ? round($this->expectedParticipants() / $estimated * 100, 1)
            : null;
    }

    /** Taxa de comparecimento; null quando não há participantes previstos. */
    public function attendanceRate(): ?float
    {
        $expected = $this->expectedParticipants();

        return $expected > 0
            ? round($this->present() / $expected * 100, 1)
            : null;
    }

    /** Há conflitos de vínculo pendentes de resolução (CA-06)? */
    public function hasPendingConflicts(): bool
    {
        return $this->event->relationships()
            ->where('is_conflict', true)
            ->whereNull('resolved_at')
            ->exists();
    }

    private function baseConfirmed()
    {
        return $this->event->guests()
            ->where('rsvp_status', RsvpStatus::Confirmado->value);
    }

    /**
     * Relatório da operação de confirmação ativa (M5), com denominadores
     * claros. Os estágios são derivados do último contato (ContactStage);
     * para o volume do MVP, deriva-se em PHP sobre a lista carregada.
     *
     * @return array<string,int>
     */
    public function contactReport(): array
    {
        $stage = app(ContactStage::class);

        $guests = $this->event->guests()
            ->with(['contacts' => fn ($q) => $q->latest()->latest('id')->limit(1)])
            ->get();

        $byStage = [
            ContactStage::NAO_CONTATADO => 0,
            ContactStage::SEM_RESPOSTA => 0,
            ContactStage::RETORNO_AGENDADO => 0,
            ContactStage::RETORNO_VENCIDO => 0,
            ContactStage::RESPONDIDO => 0,
            ContactStage::RECONFIRMADO => 0,
        ];

        foreach ($guests as $guest) {
            $s = $stage->for($guest, $guest->contacts->first());
            $byStage[$s]++;
        }

        $total = $guests->count();
        $contatados = $total - $byStage[ContactStage::NAO_CONTATADO];

        return [
            'total_lista' => $total,
            'nao_contatados' => $byStage[ContactStage::NAO_CONTATADO],
            'contatados' => $contatados,
            'sem_resposta' => $byStage[ContactStage::SEM_RESPOSTA],
            'retornos_agendados' => $byStage[ContactStage::RETORNO_AGENDADO],
            'retornos_vencidos' => $byStage[ContactStage::RETORNO_VENCIDO],
            'respondidos' => $byStage[ContactStage::RESPONDIDO],
            'reconfirmados' => $byStage[ContactStage::RECONFIRMADO],
        ];
    }
}
