<?php

namespace App\Enums;

/**
 * Canal usado numa tentativa de contato com o convidado (M1).
 * A ligação é o canal primário; WhatsApp apoia a reconfirmação quando
 * disponível; "outro" cobre casos manuais (e-mail, presencial, etc.).
 */
enum ContactChannel: string
{
    case Ligacao = 'ligacao';
    case Whatsapp = 'whatsapp';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Ligacao => 'Ligação',
            self::Whatsapp => 'WhatsApp',
            self::Outro => 'Outro',
        };
    }
}
