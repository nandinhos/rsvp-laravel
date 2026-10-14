<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Monta a consulta da lista de convidados de um evento (PRD §5),
 * aplicando busca, filtros e ordenação consistente.
 *
 * Centralizar aqui garante que listagem e exportação (CA-11) usem
 * exatamente os mesmos critérios.
 */
class GuestListQuery
{
    /**
     * @param  array{search?:string,category?:int,role?:string,rsvp?:string,attendance?:string}  $filters
     */
    public function build(Event $event, array $filters = []): Builder
    {
        $query = $event->guests()->with('category')->getQuery();

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyCategory($query, $filters['category'] ?? null);
        $this->applyEnumFilter($query, 'participation_role', $filters['role'] ?? null, ParticipationRole::class);
        $this->applyEnumFilter($query, 'rsvp_status', $filters['rsvp'] ?? null, RsvpStatus::class);
        $this->applyEnumFilter($query, 'attendance_status', $filters['attendance'] ?? null, AttendanceStatus::class);

        // Ordenação consistente e estável pela numeração do evento (PRD §2.1, §5).
        return $query->orderBy('number');
    }

    private function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);

        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            // Busca case-insensitive e portável (PostgreSQL e SQLite):
            // compara em minúsculas nos dois lados.
            $term = '%'.mb_strtolower($search).'%';
            $q->whereRaw('LOWER(full_name) LIKE ?', [$term]);

            if (ctype_digit($search)) {
                $q->orWhere('number', (int) $search);
            }
        });
    }

    private function applyCategory(Builder $query, int|string|null $categoryId): void
    {
        if ($categoryId !== null && $categoryId !== '') {
            $query->where('guest_category_id', (int) $categoryId);
        }
    }

    /**
     * Aplica filtro por valor de enum, ignorando valores inválidos
     * (nunca lança: valor inválido simplesmente não filtra).
     *
     * @param  class-string  $enumClass
     */
    private function applyEnumFilter(Builder $query, string $column, ?string $value, string $enumClass): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($enumClass::tryFrom($value) !== null) {
            $query->where($column, $value);
        }
    }
}
