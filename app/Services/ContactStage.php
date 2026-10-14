<?php

namespace App\Services;

use App\Enums\ContactOutcome;
use App\Models\Guest;
use App\Models\GuestContact;

/**
 * Estágio de contato DERIVADO do histórico (M2).
 *
 * Não altera o enum RsvpStatus: é uma leitura operacional de "onde está"
 * cada convidado no processo de confirmação ativa, calculada a partir do
 * último GuestContact. Mantém o domínio RSVP intacto.
 *
 * Estágios possíveis:
 *  - nao_contatado:    nenhum contato registrado;
 *  - sem_resposta:     última tentativa sem resposta;
 *  - retorno_agendado: retorno marcado para o futuro;
 *  - retorno_vencido:  retorno marcado já no passado (ação necessária);
 *  - respondido:       confirmou/recusou/contato inválido;
 *  - reconfirmado:     última ação foi reconfirmação.
 */
class ContactStage
{
    public const NAO_CONTATADO = 'nao_contatado';
    public const SEM_RESPOSTA = 'sem_resposta';
    public const RETORNO_AGENDADO = 'retorno_agendado';
    public const RETORNO_VENCIDO = 'retorno_vencido';
    public const RESPONDIDO = 'respondido';
    public const RECONFIRMADO = 'reconfirmado';

    public const LABELS = [
        self::NAO_CONTATADO => 'Não contatado',
        self::SEM_RESPOSTA => 'Sem resposta',
        self::RETORNO_AGENDADO => 'Retorno agendado',
        self::RETORNO_VENCIDO => 'Retorno vencido',
        self::RESPONDIDO => 'Respondido',
        self::RECONFIRMADO => 'Reconfirmado',
    ];

    /**
     * Deriva o estágio. $last pode ser passado (lista já carregou o último
     * contato) para evitar nova consulta; caso contrário, busca o mais recente.
     */
    public function for(Guest $guest, ?GuestContact $last = null): string
    {
        $last ??= $guest->contacts()->first();

        if ($last === null) {
            return self::NAO_CONTATADO;
        }

        return match ($last->outcome) {
            ContactOutcome::Reconfirmado => self::RECONFIRMADO,
            ContactOutcome::Confirmou,
            ContactOutcome::Recusou,
            ContactOutcome::ContatoInvalido => self::RESPONDIDO,
            ContactOutcome::SemResposta => self::SEM_RESPOSTA,
            ContactOutcome::RetornoAgendado => $this->returnStage($last),
        };
    }

    public static function label(string $stage): string
    {
        return self::LABELS[$stage] ?? $stage;
    }

    private function returnStage(GuestContact $last): string
    {
        if ($last->scheduled_return_at !== null && $last->scheduled_return_at->isPast()) {
            return self::RETORNO_VENCIDO;
        }

        return self::RETORNO_AGENDADO;
    }
}
