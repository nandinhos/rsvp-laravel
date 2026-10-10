<?php

namespace App\Enums;

/**
 * Estado de presença física (PRD §2.6). Distinto de RSVP.
 * Ausência só é identificável após o encerramento do evento.
 */
enum AttendanceStatus: string
{
    case Aguardando = 'aguardando';
    case Presente = 'presente';

    public function label(): string
    {
        return match ($this) {
            self::Aguardando => 'Aguardando credenciamento',
            self::Presente => 'Presente',
        };
    }

    public function isPresent(): bool
    {
        return $this === self::Presente;
    }
}
