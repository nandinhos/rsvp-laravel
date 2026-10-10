<?php

namespace App\Enums;

/**
 * Papel de participação do convidado no evento (PRD §2).
 * Papel é um conceito INDEPENDENTE de categoria, RSVP e presença.
 * O papel "atual" pode mudar: um acompanhante confirmado de titular
 * não confirmado é promovido a representante (CA-05).
 */
enum ParticipationRole: string
{
    case Titular = 'titular';
    case Acompanhante = 'acompanhante';
    case Representante = 'representante';

    public function label(): string
    {
        return match ($this) {
            self::Titular => 'Titular',
            self::Acompanhante => 'Acompanhante',
            self::Representante => 'Representante',
        };
    }
}
