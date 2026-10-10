<?php

namespace App\Enums;

/**
 * Estado de confirmação RSVP (PRD §2.3).
 * Três estados DISTINTOS: pendente nunca é recusa automática (CA-03).
 * Na importação, 1 => Confirmado, 0 => Pendente (jamais NaoParticipara).
 */
enum RsvpStatus: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case NaoParticipara = 'nao_participara';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Pendente',
            self::Confirmado => 'Confirmado',
            self::NaoParticipara => 'Não participará',
        };
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmado;
    }

    /**
     * Mapeia o valor da importação simplificada para o estado inicial.
     * 1 => Confirmado; qualquer outro (incl. 0) => Pendente (CA-03).
     */
    public static function fromImportFlag(int|string|null $flag): self
    {
        return (string) $flag === '1' ? self::Confirmado : self::Pendente;
    }
}
