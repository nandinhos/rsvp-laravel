<?php

namespace App\Enums;

/**
 * Perfil do usuário dentro de um evento específico (PRD §8).
 * A autorização é sempre por evento (EventUser), nunca global.
 */
enum EventRole: string
{
    case Administrador = 'administrador';
    case Gestor = 'gestor';
    case Operador = 'operador';
    case Leitor = 'leitor';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Gestor => 'Gestor do evento',
            self::Operador => 'Operador',
            self::Leitor => 'Leitor',
        };
    }

    /** Pode administrar convidados, confirmações, vínculos e relatórios. */
    public function canManageGuests(): bool
    {
        return in_array($this, [self::Administrador, self::Gestor], true);
    }

    /** Pode confirmar RSVP e realizar check-in. */
    public function canOperate(): bool
    {
        return in_array($this, [self::Administrador, self::Gestor, self::Operador], true);
    }

    /** Pode exportar dados do evento. */
    public function canExport(): bool
    {
        return in_array($this, [self::Administrador, self::Gestor], true);
    }

    /** Pode gerenciar permissões e configurações do evento. */
    public function canAdminister(): bool
    {
        return $this === self::Administrador;
    }
}
