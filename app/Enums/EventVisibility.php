<?php

namespace App\Enums;

/**
 * Visibilidade do evento (PRD §3). Público ou reservado.
 * A classificação pública não autoriza divulgação de dados pessoais dos convidados.
 */
enum EventVisibility: string
{
    case Publico = 'publico';
    case Reservado = 'reservado';

    public function label(): string
    {
        return match ($this) {
            self::Publico => 'Público',
            self::Reservado => 'Reservado',
        };
    }
}
