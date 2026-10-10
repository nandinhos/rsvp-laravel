<?php

namespace App\Services\Import;

use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GuestNumberAllocator;
use App\Services\GuestRelationshipService;
use App\Services\RsvpService;
use Illuminate\Support\Facades\DB;

/**
 * Persiste os registros válidos da importação de forma transacional
 * (PRD §6 passos 8–10, CA-08). Resolve vínculos após a criação de todos
 * os convidados do lote, aplica RSVP e registra a auditoria.
 *
 * Pré-condição: só recebe linhas SEM erros (validadas pelo ImportAnalyzer).
 */
class ImportPersister
{
    public function __construct(
        private readonly GuestNumberAllocator $allocator,
        private readonly GuestRelationshipService $relationships,
        private readonly RsvpService $rsvp,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  ImportRow[]  $rows  linhas válidas
     * @return int quantidade de convidados criados
     */
    public function persist(Event $event, array $rows, User $operator): int
    {
        return DB::transaction(function () use ($event, $rows, $operator) {
            $categories = $event->categories()->get()
                ->keyBy(fn (GuestCategory $c) => mb_strtolower(trim($c->name)));

            // 1ª passada: cria todos os convidados, indexando por nome.
            $created = [];
            $byName = [];
            foreach ($rows as $row) {
                $category = $categories->get(mb_strtolower($row->category));

                $guest = Guest::create([
                    'event_id' => $event->id,
                    'number' => $this->allocator->next($event),
                    'full_name' => $row->name,
                    'guest_category_id' => $category->id,
                    'rsvp_status' => RsvpStatus::Pendente->value, // confirma depois, aplicando regras
                ]);

                $created[] = ['guest' => $guest, 'row' => $row];
                $byName[mb_strtolower($row->name)] = $guest;
            }

            // 2ª passada: vínculos (todos como acompanhante; promoção acontece no RSVP).
            foreach ($created as $item) {
                $row = $item['row'];
                if ($row->relation === '') {
                    continue;
                }

                $host = $byName[mb_strtolower($row->relation)] ?? null;
                if ($host !== null) {
                    $this->relationships->attachCompanion($item['guest'], $host);
                }
            }

            // 3ª passada: aplica RSVP confirmado (dispara promoção/conflito).
            foreach ($created as $item) {
                if ($item['row']->rsvp === RsvpStatus::Confirmado->value) {
                    $this->rsvp->confirm($item['guest']->fresh());
                }
            }

            $this->audit->log(
                action: 'guests.imported',
                user: $operator,
                event: $event,
                changes: ['count' => count($created)],
            );

            return count($created);
        });
    }
}
