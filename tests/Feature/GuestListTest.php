<?php

use App\Enums\AttendanceStatus;
use App\Enums\EventRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function member(Event $event, EventRole $role): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

/**
 * CA-01 — isolamento no nível HTTP.
 */
it('nega listagem a quem não pertence ao evento (CA-01)', function () {
    $event = Event::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('guests.index', $event->id))
        ->assertForbidden();
});

it('permite listagem a membro do evento', function () {
    $event = Event::factory()->create();
    $leitor = member($event, EventRole::Leitor);

    $this->actingAs($leitor)
        ->get(route('guests.index', $event->id))
        ->assertOk();
});

it('impede operar convidado de outro evento via URL cruzada (CA-01)', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $operador = member($eventA, EventRole::Operador);
    $guestB = Guest::factory()->inEvent($eventB)->create();

    // Operador do A tenta confirmar convidado do B usando a rota do A.
    $this->actingAs($operador)
        ->post(route('guests.confirm', [$eventA->id, $guestB->id]))
        ->assertNotFound();

    expect($guestB->fresh()->rsvp_status)->toBe(RsvpStatus::Pendente);
});

/**
 * Papéis: leitor não opera, operador opera.
 */
it('leitor não pode confirmar nem credenciar', function () {
    $event = Event::factory()->create();
    $leitor = member($event, EventRole::Leitor);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($leitor)
        ->post(route('guests.confirm', [$event->id, $guest->id]))
        ->assertForbidden();
});

it('operador confirma e credencia pela ação rápida', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)
        ->post(route('guests.confirm', [$event->id, $guest->id]))
        ->assertRedirect();
    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);

    $this->actingAs($operador)
        ->post(route('guests.check-in', [$event->id, $guest->id]))
        ->assertRedirect();
    $guest->refresh();
    expect($guest->attendance_status)->toBe(AttendanceStatus::Presente);
    expect($guest->checked_in_by)->toBe($operador->id);
    expect($guest->checked_in_at)->not->toBeNull();
});

/**
 * CA-09 — check-ins repetidos não produzem presenças duplicadas.
 */
it('check-in é idempotente e preserva o primeiro credenciamento (CA-09)', function () {
    $event = Event::factory()->create();
    $op1 = member($event, EventRole::Operador);
    $op2 = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->confirmed()->create();

    $service = app(CheckInService::class);

    $first = $service->checkIn($guest, $op1);
    $firstAt = $first->checked_in_at;
    $firstBy = $first->checked_in_by;

    // Segundo check-in por outro operador não altera o registro original.
    $second = $service->checkIn($guest->fresh(), $op2);

    expect($second->attendance_status)->toBe(AttendanceStatus::Presente);
    expect($second->checked_in_by)->toBe($firstBy);
    expect($second->checked_in_at->equalTo($firstAt))->toBeTrue();

    // Apenas uma presença no evento.
    expect($event->guests()->where('attendance_status', AttendanceStatus::Presente->value)->count())->toBe(1);
});

/**
 * CA-14 (parcial) — bloqueio de alterações operacionais em evento encerrado.
 */
it('bloqueia ações em evento encerrado (CA-14)', function () {
    $event = Event::factory()->closed()->create();
    $operador = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)
        ->post(route('guests.confirm', [$event->id, $guest->id]))
        ->assertForbidden();

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Pendente);
});

/**
 * Busca e filtros (PRD §5).
 */
it('filtra a lista por confirmação', function () {
    $event = Event::factory()->create();
    $gestor = member($event, EventRole::Gestor);

    Guest::factory()->inEvent($event)->create(['full_name' => 'Ana', 'rsvp_status' => RsvpStatus::Confirmado]);
    Guest::factory()->inEvent($event)->create(['full_name' => 'Bruno', 'rsvp_status' => RsvpStatus::Pendente]);

    $this->actingAs($gestor)
        ->get(route('guests.index', [$event->id, 'rsvp' => 'confirmado']))
        ->assertInertia(fn ($page) => $page
            ->component('Guests/Index')
            ->has('guests.data', 1)
            ->where('guests.data.0.full_name', 'Ana')
        );
});

it('busca por número encontra o convidado exato', function () {
    $event = Event::factory()->create();
    $gestor = member($event, EventRole::Gestor);
    $g = Guest::factory()->inEvent($event)->create(['full_name' => 'Carlos']);

    $this->actingAs($gestor)
        ->get(route('guests.index', [$event->id, 'search' => (string) $g->number]))
        ->assertInertia(fn ($page) => $page
            ->has('guests.data', 1)
            ->where('guests.data.0.id', $g->id)
        );
});

/**
 * Criação com numeração automática e categoria do evento (CA-02, CA-13).
 */
it('gestor cadastra convidado com numeração automática', function () {
    $event = Event::factory()->create();
    $gestor = member($event, EventRole::Gestor);
    $category = GuestCategory::factory()->for($event)->create();

    $this->actingAs($gestor)
        ->post(route('guests.store', $event->id), [
            'full_name' => 'Diana Prince',
            'guest_category_id' => $category->id,
        ])
        ->assertRedirect();

    $guest = $event->guests()->first();
    expect($guest->full_name)->toBe('Diana Prince');
    expect($guest->number)->toBe(1);
    expect($guest->rsvp_status)->toBe(RsvpStatus::Pendente);
});

it('rejeita cadastro com categoria de outro evento (CA-02)', function () {
    $event = Event::factory()->create();
    $outro = Event::factory()->create();
    $gestor = member($event, EventRole::Gestor);
    $categoriaDeOutro = GuestCategory::factory()->for($outro)->create();

    $this->actingAs($gestor)
        ->post(route('guests.store', $event->id), [
            'full_name' => 'Intruso',
            'guest_category_id' => $categoriaDeOutro->id,
        ]);

    expect($event->guests()->count())->toBe(0);
});

/**
 * Reversões (desfazer imediato) — padrão de undo.
 */
it('operador desfaz o credenciamento (revert-check-in)', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->confirmed()->create();

    app(CheckInService::class)->checkIn($guest, $operador);
    expect($guest->fresh()->attendance_status)->toBe(AttendanceStatus::Presente);

    $this->actingAs($operador)
        ->post(route('guests.revert-check-in', [$event->id, $guest->id]))
        ->assertRedirect();

    $guest->refresh();
    expect($guest->attendance_status)->toBe(AttendanceStatus::Aguardando);
    expect($guest->checked_in_at)->toBeNull();
    expect($guest->checked_in_by)->toBeNull();
});

it('operador desfaz a confirmação de RSVP (undo-rsvp)', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Confirmado]);

    $this->actingAs($operador)
        ->post(route('guests.undo-rsvp', [$event->id, $guest->id]))
        ->assertRedirect();

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Pendente);
});

it('leitor não pode desfazer credenciamento nem RSVP', function () {
    $event = Event::factory()->create();
    $leitor = member($event, EventRole::Leitor);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($leitor)
        ->post(route('guests.revert-check-in', [$event->id, $guest->id]))
        ->assertForbidden();

    $this->actingAs($leitor)
        ->post(route('guests.undo-rsvp', [$event->id, $guest->id]))
        ->assertForbidden();
});

it('impede desfazer em convidado de outro evento via URL cruzada (CA-01)', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $operador = member($eventA, EventRole::Operador);
    $guestB = Guest::factory()->inEvent($eventB)->create();

    $this->actingAs($operador)
        ->post(route('guests.revert-check-in', [$eventA->id, $guestB->id]))
        ->assertNotFound();
});

it('bloqueia reversões em evento encerrado (CA-14)', function () {
    $event = Event::factory()->closed()->create();
    $operador = member($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Confirmado]);

    $this->actingAs($operador)
        ->post(route('guests.undo-rsvp', [$event->id, $guest->id]))
        ->assertForbidden();

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);
});

/**
 * Estação de credenciamento (portaria).
 */
it('nega estação de credenciamento a quem não pertence ao evento (CA-01)', function () {
    $event = Event::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('guests.check-in-station', $event->id))
        ->assertForbidden();
});

it('renderiza a estação com aptos, pendentes e contadores ao vivo', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);

    Guest::factory()->inEvent($event)->confirmed()->create(['full_name' => 'Ana']);
    $presente = Guest::factory()->inEvent($event)->confirmed()->create(['full_name' => 'Bruno']);
    app(CheckInService::class)->checkIn($presente, $operador);
    // Um pendente: deve ir para a seção de pendentes, não de aptos.
    Guest::factory()->inEvent($event)->create(['full_name' => 'Carla', 'rsvp_status' => RsvpStatus::Pendente]);

    $this->actingAs($operador)
        ->get(route('guests.check-in-station', $event->id))
        ->assertInertia(fn ($page) => $page
            ->component('Guests/CheckIn')
            ->where('counters.total', 3)
            ->where('counters.present', 1)
            ->where('counters.awaiting', 1)
            ->where('counters.confirmed', 2)
            ->where('counters.pending', 1)
            ->has('eligible', 2)
            ->has('pending', 1)
            ->where('pending.0.full_name', 'Carla')
        );
});

it('a estação mantém ordem estável por número (credenciar não reordena)', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);

    // Ambos confirmados; #1 é credenciado (presente), #2 segue aguardando.
    $primeiro = Guest::factory()->inEvent($event)->confirmed()->create(['full_name' => 'Primeiro numero']);
    app(CheckInService::class)->checkIn($primeiro, $operador);
    Guest::factory()->inEvent($event)->confirmed()->create(['full_name' => 'Segundo numero']);

    // A ordem segue a numeração, não o estado de presença:
    // o presente #1 continua antes do que aguarda #2 na lista de aptos.
    $this->actingAs($operador)
        ->get(route('guests.check-in-station', $event->id))
        ->assertInertia(fn ($page) => $page
            ->where('eligible.0.number', $primeiro->number)
            ->where('eligible.0.attendance_status', 'presente')
            ->where('eligible.1.attendance_status', 'aguardando')
        );
});

/**
 * Regra de aptidão (validação com correção): só confirmados credenciam.
 */
it('bloqueia check-in de convidado não confirmado e não gera estado inconsistente', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);
    $pendente = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Pendente]);

    $this->actingAs($operador)
        ->post(route('guests.check-in', [$event->id, $pendente->id]))
        ->assertRedirect()
        ->assertSessionHasErrors('check_in');

    $pendente->refresh();
    // Permanece aguardando: nunca "pendente + presente".
    expect($pendente->attendance_status)->toBe(AttendanceStatus::Aguardando);
    expect($pendente->checked_in_at)->toBeNull();
});

it('serviço de check-in rejeita convidado não confirmado', function () {
    $event = Event::factory()->create();
    $operador = member($event, EventRole::Operador);
    $pendente = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Pendente]);

    expect(fn () => app(CheckInService::class)->checkIn($pendente, $operador))
        ->toThrow(App\Exceptions\GuestDomainException::class);
});
