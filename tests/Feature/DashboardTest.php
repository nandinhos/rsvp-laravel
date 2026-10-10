<?php

use App\Enums\EventRole;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\GuestRelationshipService;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashMember(Event $event, EventRole $role = EventRole::Gestor): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

/**
 * Seleção de eventos (PRD §3, CA-01).
 */
it('lista apenas eventos autorizados ao usuário (CA-01)', function () {
    $user = User::factory()->create();
    $meu = Event::factory()->create(['name' => 'Meu Evento']);
    Event::factory()->create(['name' => 'Evento Alheio']);

    $meu->users()->attach($user, ['role' => EventRole::Leitor->value]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('events', 1)
            ->where('events.0.name', 'Meu Evento')
        );
});

/**
 * CA-01 — dashboard de evento não autorizado é negado.
 */
it('nega dashboard a quem não pertence ao evento (CA-01)', function () {
    $event = Event::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('events.dashboard', $event->id))
        ->assertForbidden();
});

/**
 * CA-04 / CA-07 — indicadores corretos e sem dupla contagem.
 */
it('apresenta indicadores corretos no dashboard (CA-04, CA-07)', function () {
    $event = Event::factory()->create(['estimated_audience' => 10]);
    $gestor = dashMember($event);
    $rsvp = new RsvpService;
    $links = new GuestRelationshipService;

    // Titular + acompanhante confirmados (ambos) => 1 titular + 1 acompanhante.
    $host = Guest::factory()->inEvent($event)->create();
    $companion = Guest::factory()->inEvent($event)->create();
    $links->attachCompanion($companion, $host);
    $rsvp->confirm($host);
    $rsvp->confirm($companion);

    // Um convidado pendente.
    Guest::factory()->inEvent($event)->create();

    $this->actingAs($gestor)
        ->get(route('events.dashboard', $event->id))
        ->assertInertia(fn ($page) => $page
            ->component('Events/Dashboard')
            ->where('metrics.confirmed_hosts', 1)
            ->where('metrics.confirmed_companions', 1)
            ->where('metrics.confirmed_representatives', 0)
            ->where('metrics.expected_participants', 2)   // sem dupla contagem (CA-07)
            ->where('metrics.not_confirmed', 1)
            ->where('metrics.confirmation_rate', 20)    // 2/10 * 100
        );
});

/**
 * Proteção contra divisão por zero (PRD §4).
 */
it('mostra taxa indisponível quando público estimado é zero', function () {
    $event = Event::factory()->create(['estimated_audience' => 0]);
    $gestor = dashMember($event);

    $this->actingAs($gestor)
        ->get(route('events.dashboard', $event->id))
        ->assertInertia(fn ($page) => $page
            ->where('metrics.confirmation_rate', null)
        );
});

/**
 * CA-06 — dashboard sinaliza conflitos pendentes.
 */
it('sinaliza conflitos pendentes no dashboard (CA-06)', function () {
    $event = Event::factory()->create();
    $gestor = dashMember($event);
    $rsvp = new RsvpService;
    $links = new GuestRelationshipService;

    $host = Guest::factory()->inEvent($event)->create();
    $companion = Guest::factory()->inEvent($event)->create();
    $links->attachCompanion($companion, $host);
    $rsvp->confirm($companion); // vira representante
    $rsvp->confirm($host);      // gera conflito

    $this->actingAs($gestor)
        ->get(route('events.dashboard', $event->id))
        ->assertInertia(fn ($page) => $page
            ->where('metrics.has_pending_conflicts', true)
        );
});
