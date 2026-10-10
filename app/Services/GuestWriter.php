<?php

namespace App\Services;

use App\Enums\RsvpStatus;
use App\Exceptions\GuestDomainException;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use Illuminate\Support\Facades\DB;

/**
 * Criação e edição de convidados (PRD §5 operações).
 * Garante categoria obrigatória pertencente ao evento (CA-02) e numeração
 * sequencial atômica não reutilizada (CA-13).
 */
class GuestWriter
{
    public function __construct(private readonly GuestNumberAllocator $allocator) {}

    /**
     * @param  array{full_name:string,guest_category_id:int,rsvp_status?:string,note?:string}  $data
     */
    public function create(Event $event, array $data): Guest
    {
        $category = $this->resolveCategory($event, (int) $data['guest_category_id']);

        return DB::transaction(function () use ($event, $data, $category) {
            return Guest::create([
                'event_id' => $event->id,
                'number' => $this->allocator->next($event),
                'full_name' => trim($data['full_name']),
                'guest_category_id' => $category->id,
                'rsvp_status' => $data['rsvp_status'] ?? RsvpStatus::Pendente->value,
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{full_name?:string,guest_category_id?:int,note?:string}  $data
     */
    public function update(Guest $guest, array $data): Guest
    {
        if (isset($data['guest_category_id'])) {
            $this->resolveCategory($guest->event, (int) $data['guest_category_id']);
        }

        $guest->fill(array_filter([
            'full_name' => isset($data['full_name']) ? trim($data['full_name']) : null,
            'guest_category_id' => $data['guest_category_id'] ?? null,
            'note' => $data['note'] ?? null,
        ], fn ($v) => $v !== null))->save();

        return $guest->refresh();
    }

    private function resolveCategory(Event $event, int $categoryId): GuestCategory
    {
        $category = GuestCategory::where('id', $categoryId)
            ->where('event_id', $event->id)
            ->first();

        // CA-02 + isolamento: categoria deve existir e pertencer ao evento.
        if ($category === null) {
            throw new GuestDomainException('Categoria inválida para este evento.');
        }

        return $category;
    }
}
