<?php

use App\Enums\AttendanceStatus;
use App\Enums\EventRole;
use App\Enums\RsvpStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function contactMember(Event $event, EventRole $role): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

it('operador registra um contato (append-only, auditado)', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => 'sem_resposta',
            'note' => 'Não atendeu, tentar à tarde.',
        ])
        ->assertRedirect();

    expect($guest->contacts()->count())->toBe(1);
    $contact = $guest->contacts()->first();
    expect($contact->operator_id)->toBe($operador->id);
    expect($contact->channel->value)->toBe('ligacao');
    expect(AuditLog::where('action', 'guest.contact_recorded')->exists())->toBeTrue();
});

it('resultado "confirmou" espelha no RSVP do convidado', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Pendente]);

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => 'confirmou',
        ])
        ->assertRedirect();

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);
});

it('resultado "recusou" espelha não participará no RSVP', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Pendente]);

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => 'recusou',
        ]);

    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::NaoParticipara);
});

it('mantém o histórico append-only (não sobrescreve tentativas)', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    foreach (['sem_resposta', 'retorno_agendado', 'confirmou'] as $outcome) {
        $this->actingAs($operador)->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => $outcome,
        ]);
    }

    expect($guest->contacts()->count())->toBe(3);
});

it('registra retorno agendado com data', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)->post(route('guests.contacts.store', [$event->id, $guest->id]), [
        'channel' => 'ligacao',
        'outcome' => 'retorno_agendado',
        'scheduled_return_at' => now()->addDay()->toDateTimeString(),
    ]);

    expect($guest->contacts()->first()->scheduled_return_at)->not->toBeNull();
});

/**
 * CA-01 — isolamento: não registrar contato em convidado de outro evento.
 */
it('impede registrar contato em convidado de outro evento (CA-01)', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $operador = contactMember($eventA, EventRole::Operador);
    $guestB = Guest::factory()->inEvent($eventB)->create();

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$eventA->id, $guestB->id]), [
            'channel' => 'ligacao',
            'outcome' => 'sem_resposta',
        ])
        ->assertNotFound();

    expect(GuestContact::count())->toBe(0);
});

it('leitor não pode registrar contato', function () {
    $event = Event::factory()->create();
    $leitor = contactMember($event, EventRole::Leitor);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($leitor)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => 'sem_resposta',
        ])
        ->assertForbidden();
});

it('bloqueia registro de contato em evento encerrado (CA-14)', function () {
    $event = Event::factory()->closed()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'ligacao',
            'outcome' => 'sem_resposta',
        ])
        ->assertForbidden();

    expect(GuestContact::count())->toBe(0);
});

it('rejeita canal ou resultado inválido', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create();

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'telepatia',
            'outcome' => 'sem_resposta',
        ])
        ->assertSessionHasErrors('channel');
});

it('registra reconfirmacao por WhatsApp e deriva estagio reconfirmado (M4)', function () {
    $event = Event::factory()->create();
    $operador = contactMember($event, EventRole::Operador);
    $guest = Guest::factory()->inEvent($event)->create(['rsvp_status' => RsvpStatus::Confirmado]);

    $this->actingAs($operador)
        ->post(route('guests.contacts.store', [$event->id, $guest->id]), [
            'channel' => 'whatsapp',
            'outcome' => 'reconfirmado',
        ])
        ->assertRedirect();

    $contact = $guest->contacts()->first();
    expect($contact->channel->value)->toBe('whatsapp');
    expect($contact->outcome->value)->toBe('reconfirmado');
    // Reconfirmar mantém/garante o RSVP confirmado.
    expect($guest->fresh()->rsvp_status)->toBe(RsvpStatus::Confirmado);
    // Estágio derivado é "reconfirmado".
    expect(app(App\Services\ContactStage::class)->for($guest->fresh()))
        ->toBe(App\Services\ContactStage::RECONFIRMADO);
});
