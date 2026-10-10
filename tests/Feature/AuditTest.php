<?php

use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\RsvpStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Services\GuestRelationshipService;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditMember(Event $event, EventRole $role): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

/**
 * CA-14 — encerramento bloqueia operações; reabertura é auditada.
 */
it('encerra o evento e audita (CA-14)', function () {
    $event = Event::factory()->create();
    $admin = auditMember($event, EventRole::Administrador);

    $this->actingAs($admin)->post(route('events.close', $event->id))->assertRedirect();

    expect($event->fresh()->status)->toBe(EventStatus::Encerrado);
    expect($event->fresh()->closed_at)->not->toBeNull();
    expect(AuditLog::where('action', 'event.closed')->where('event_id', $event->id)->exists())->toBeTrue();
});

it('reabre o evento e audita a reabertura (CA-14)', function () {
    $event = Event::factory()->closed()->create();
    $admin = auditMember($event, EventRole::Administrador);

    $this->actingAs($admin)->post(route('events.reopen', $event->id))->assertRedirect();

    expect($event->fresh()->status)->toBe(EventStatus::Ativo);
    expect(AuditLog::where('action', 'event.reopened')->where('event_id', $event->id)->exists())->toBeTrue();
});

it('somente administrador pode encerrar (CA-14)', function () {
    $event = Event::factory()->create();
    $gestor = auditMember($event, EventRole::Gestor);

    $this->actingAs($gestor)->post(route('events.close', $event->id))->assertForbidden();
    expect($event->fresh()->status)->not->toBe(EventStatus::Encerrado);
});

/**
 * CA-10 — operações críticas são auditadas.
 */
it('audita confirmação e check-in (CA-10)', function () {
    $event = Event::factory()->create();
    $operador = auditMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)->post(route('guests.confirm', [$event->id, $guest->id]));
    $this->actingAs($operador)->post(route('guests.check-in', [$event->id, $guest->id]));

    expect(AuditLog::where('action', 'guest.confirmed')->exists())->toBeTrue();
    expect(AuditLog::where('action', 'guest.checked_in')->exists())->toBeTrue();

    $log = AuditLog::where('action', 'guest.confirmed')->first();
    expect($log->user_id)->toBe($operador->id);
    expect($log->event_id)->toBe($event->id);
});

/**
 * CA-06 — resolução explícita do conflito.
 */
it('resolve conflito mantendo o titular (CA-06)', function () {
    $event = Event::factory()->create();
    $operador = auditMember($event, EventRole::Operador);
    $rsvp = new RsvpService;
    $links = new GuestRelationshipService;

    $host = Guest::factory()->inEvent($event)->create();
    $rep = Guest::factory()->inEvent($event)->create();
    $links->attachCompanion($rep, $host);
    $rsvp->confirm($rep);  // rep vira representante
    $rsvp->confirm($host); // conflito

    $link = $rep->hostRelationship()->first();
    expect($link->is_conflict)->toBeTrue();

    $this->actingAs($operador)
        ->post(route('conflicts.resolve', [$event->id, $link->id]), ['keep' => 'host'])
        ->assertRedirect();

    $link->refresh();
    expect($link->is_conflict)->toBeFalse();
    expect($link->resolved_at)->not->toBeNull();
    expect($link->resolved_by)->toBe($operador->id);

    // Representante marcado como não participará; titular mantido.
    expect($rep->fresh()->rsvp_status)->toBe(RsvpStatus::NaoParticipara);
    expect($host->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);

    expect(AuditLog::where('action', 'conflict.resolved')->exists())->toBeTrue();
});

it('resolve conflito mantendo o representante (CA-06)', function () {
    $event = Event::factory()->create();
    $operador = auditMember($event, EventRole::Operador);
    $rsvp = new RsvpService;
    $links = new GuestRelationshipService;

    $host = Guest::factory()->inEvent($event)->create();
    $rep = Guest::factory()->inEvent($event)->create();
    $links->attachCompanion($rep, $host);
    $rsvp->confirm($rep);
    $rsvp->confirm($host);

    $link = $rep->hostRelationship()->first();

    $this->actingAs($operador)
        ->post(route('conflicts.resolve', [$event->id, $link->id]), ['keep' => 'representative']);

    expect($host->fresh()->rsvp_status)->toBe(RsvpStatus::NaoParticipara);
    expect($rep->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);
});

/**
 * CA-10 — página de auditoria protegida (somente administrador).
 */
it('apenas administrador acessa a página de auditoria', function () {
    $event = Event::factory()->create();
    $gestor = auditMember($event, EventRole::Gestor);
    $admin = auditMember($event, EventRole::Administrador);

    $this->actingAs($gestor)->get(route('events.audit', $event->id))->assertForbidden();
    $this->actingAs($admin)->get(route('events.audit', $event->id))->assertOk();
});

it('audita log é append-only (não atualiza)', function () {
    $log = AuditLog::create(['action' => 'test', 'created_at' => now()]);

    expect($log->updated_at ?? null)->toBeNull();
});
