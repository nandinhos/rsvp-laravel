<?php

namespace App\Enums;

/**
 * Resultado de uma tentativa de contato (M1). Vocabulário inicial do
 * relatório técnico — alterável sem quebrar o domínio, pois o estágio de
 * contato é derivado (M2) e o RSVP permanece com seus próprios estados.
 */
enum ContactOutcome: string
{
    case SemResposta = 'sem_resposta';
    case RetornoAgendado = 'retorno_agendado';
    case Confirmou = 'confirmou';
    case Recusou = 'recusou';
    case ContatoInvalido = 'contato_invalido';
    case Reconfirmado = 'reconfirmado';

    public function label(): string
    {
        return match ($this) {
            self::SemResposta => 'Sem resposta',
            self::RetornoAgendado => 'Retorno agendado',
            self::Confirmou => 'Confirmou',
            self::Recusou => 'Recusou',
            self::ContatoInvalido => 'Contato inválido',
            self::Reconfirmado => 'Reconfirmado',
        };
    }

    /** Resultados que refletem uma decisão de RSVP a espelhar no convidado. */
    public function reflectsConfirmation(): bool
    {
        return $this === self::Confirmou || $this === self::Reconfirmado;
    }

    public function reflectsDecline(): bool
    {
        return $this === self::Recusou;
    }
}
