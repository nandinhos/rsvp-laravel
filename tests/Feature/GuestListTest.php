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
    $guest = Guest::factory()->inEvent($event)->create();

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
