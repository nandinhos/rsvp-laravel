<?php

use App\Enums\EventRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * CA-01 — Isolamento: um usuário sem acesso ao evento B não consegue
 * consultar, alterar, importar ou exportar seus dados.
 */
it('nega acesso a evento ao qual o usuário não pertence (CA-01)', function () {
    $user = User::factory()->create();
    $eventB = Event::factory()->create();

    expect($user->can('view', $eventB))->toBeFalse();
    expect($user->can('manageGuests', $eventB))->toBeFalse();
    expect($user->can('operate', $eventB))->toBeFalse();
    expect($user->can('export', $eventB))->toBeFalse();
    expect($user->can('administer', $eventB))->toBeFalse();
});

it('permite visualizar apenas eventos vinculados (CA-01)', function () {
    $user = User::factory()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();

    $eventA->users()->attach($user, ['role' => EventRole::Leitor->value]);

    expect($user->can('view', $eventA))->toBeTrue();
    expect($user->can('view', $eventB))->toBeFalse();
});

it('aplica privilégio mínimo por papel', function () {
    $leitor = User::factory()->create();
    $operador = User::factory()->create();
    $gestor = User::factory()->create();
    $admin = User::factory()->create();
    $event = Event::factory()->create();

    $event->users()->attach($leitor, ['role' => EventRole::Leitor->value]);
    $event->users()->attach($operador, ['role' => EventRole::Operador->value]);
    $event->users()->attach($gestor, ['role' => EventRole::Gestor->value]);
    $event->users()->attach($admin, ['role' => EventRole::Administrador->value]);

    // Leitor: só visualiza.
    expect($leitor->can('view', $event))->toBeTrue();
    expect($leitor->can('operate', $event))->toBeFalse();
    expect($leitor->can('manageGuests', $event))->toBeFalse();

    // Operador: visualiza e opera (confirma/check-in), mas não gerencia nem exporta.
    expect($operador->can('operate', $event))->toBeTrue();
    expect($operador->can('manageGuests', $event))->toBeFalse();
    expect($operador->can('export', $event))->toBeFalse();

    // Gestor: gerencia convidados e exporta, mas não administra permissões.
    expect($gestor->can('manageGuests', $event))->toBeTrue();
    expect($gestor->can('export', $event))->toBeTrue();
    expect($gestor->can('administer', $event))->toBeFalse();

    // Administrador: tudo.
    expect($admin->can('administer', $event))->toBeTrue();
    expect($admin->can('export', $event))->toBeTrue();
});

it('mantém papéis independentes entre eventos (CA-01)', function () {
    $user = User::factory()->create();
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();

    $eventA->users()->attach($user, ['role' => EventRole::Administrador->value]);
    $eventB->users()->attach($user, ['role' => EventRole::Leitor->value]);

    expect($user->roleFor($eventA))->toBe(EventRole::Administrador);
    expect($user->roleFor($eventB))->toBe(EventRole::Leitor);
    expect($user->can('administer', $eventA))->toBeTrue();
    expect($user->can('administer', $eventB))->toBeFalse();
});

/**
 * Seletor de evento no header: a prop compartilhada authorizedEvents
 * expõe somente os eventos vinculados ao usuário (CA-01).
 */
it('compartilha apenas eventos autorizados no header (CA-01)', function () {
    $user = User::factory()->create();
    $meu = Event::factory()->create(['name' => 'Meu Evento']);
    Event::factory()->create(['name' => 'Evento Alheio']);

    $meu->users()->attach($user, ['role' => EventRole::Operador->value]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn ($page) => $page
            ->has('authorizedEvents', 1)
            ->where('authorizedEvents.0.name', 'Meu Evento')
            ->where('authorizedEvents.0.id', $meu->id)
        );
});
