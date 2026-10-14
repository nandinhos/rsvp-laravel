<?php

namespace App\Console\Commands;

use App\Services\RetentionPurger;
use Illuminate\Console\Command;

/**
 * Expurgo de retenção (LGPD). Anonimiza convidados e elimina contatos de
 * eventos encerrados além do prazo de retenção configurado (retention_days).
 */
class PurgeExpiredGuests extends Command
{
    protected $signature = 'guests:purge';

    protected $description = 'Anonimiza convidados e elimina contatos de eventos encerrados além da retenção (LGPD)';

    public function handle(RetentionPurger $purger): int
    {
        $result = $purger->purge();

        $this->info(sprintf(
            'Expurgo concluído: %d evento(s), %d convidado(s) anonimizado(s), %d contato(s) eliminado(s).',
            $result['events'],
            $result['guests'],
            $result['contacts'],
        ));

        return self::SUCCESS;
    }
}
