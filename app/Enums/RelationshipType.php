<?php

namespace App\Enums;

/**
 * Tipo de vínculo entre dois convidados (PRD §2.4 e §2.5).
 * Guarda a NATUREZA do relacionamento. Um vínculo de acompanhante pode
 * ter sido promovido a representante preservando sua origem (CA-05),
 * por isso a origem é rastreada separadamente no GuestRelationship.
 */
enum RelationshipType: string
{
    case Acompanhante = 'acompanhante';
    case Representante = 'representante';

    public function label(): string
    {
        return match ($this) {
            self::Acompanhante => 'Acompanhante',
            self::Representante => 'Representante',
        };
    }
}
