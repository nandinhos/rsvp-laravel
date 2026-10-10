<?php

namespace App\Enums;

/**
 * Ciclo de vida do evento (PRD §3): rascunho, ativo ou encerrado.
 * Eventos encerrados bloqueiam alterações operacionais (CA-14).
 */
enum EventStatus: string
{
    case Rascunho = 'rascunho';
    case Ativo = 'ativo';
    case Encerrado = 'encerrado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Ativo => 'Ativo',
            self::Encerrado => 'Encerrado',
        };
    }

    /** Evento encerrado impede operações comuns, salvo reabertura auditada. */
    public function isClosed(): bool
    {
        return $this === self::Encerrado;
    }
}
