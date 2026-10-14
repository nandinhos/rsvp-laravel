<?php

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Enums\EventRole;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Models\User;
use App\Services\ContactStage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function queueMember(Event $event, EventRole $role): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

function queueContact(Guest $guest, ContactOutcome $outcome, ?string $returnAt = null): void
{
    GuestContact::create([
        'event_id' => $guest->event_id,
        'guest_id' => $guest->id,
        'channel' => ContactChannel::Ligacao,
        'outcome' => $outcome,
        'scheduled_return_at' => $returnAt,
    ]);
}

it('nega fila de trabalho a quem não pertence ao evento (CA-01)', function () {
    $event = Event::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('guests.work-queue', $event->id))
        ->assertForbidden();
});

it('prioriza retorno vencido e não contatado no topo da fila', function () {
    $event = Event::factory()->create();
    $operador = queueMember($event, EventRole::Operador);

    // Respondido (baixa prioridade).
    $respondido = Guest::factory()->inEvent($event)->create(['full_name' => 'Respondido']);
    queueContact($respondido, ContactOutcome::Confirmou);

    // Não contatado (sem contato).
    $naoContatado = Guest::factory()->inEvent($event)->create(['full_name' => 'Nao Contatado']);

    // Retorno vencido (prioridade máxima).
    $vencido = Guest::factory()->inEvent($event)->create(['full_name' => 'Vencido']);
    queueContact($vencido, ContactOutcome::RetornoAgendado, now()->subDay()->toDateTimeString());

    $this->actingAs($operador)
        ->get(route('guests.work-queue', $event->id))
        ->assertInertia(fn ($page) => $page
            ->component('Guests/WorkQueue')
            ->where('guests.0.contact_stage', ContactStage::RETORNO_VENCIDO)
            ->where('guests.1.contact_stage', ContactStage::NAO_CONTATADO)
            ->where('guests.2.contact_stage', ContactStage::RESPONDIDO)
        );
});

it('permite leitor visualizar a fila (view)', function () {
    $event = Event::factory()->create();
    $leitor = queueMember($event, EventRole::Leitor);
    Guest::factory()->inEvent($event)->create();

    $this->actingAs($leitor)
        ->get(route('guests.work-queue', $event->id))
        ->assertOk();
});
