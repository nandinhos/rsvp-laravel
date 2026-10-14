<?php

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Services\ContactStage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stageGuest(): Guest
{
    $event = Event::factory()->create();

    return Guest::factory()->inEvent($event)->create();
}

function addContact(Guest $guest, ContactOutcome $outcome, ?string $returnAt = null): void
{
    GuestContact::create([
        'event_id' => $guest->event_id,
        'guest_id' => $guest->id,
        'channel' => ContactChannel::Ligacao,
        'outcome' => $outcome,
        'scheduled_return_at' => $returnAt,
    ]);
}

it('sem contato => nao_contatado', function () {
    $guest = stageGuest();
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::NAO_CONTATADO);
});

it('ultima tentativa sem resposta => sem_resposta', function () {
    $guest = stageGuest();
    addContact($guest, ContactOutcome::SemResposta);
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::SEM_RESPOSTA);
});

it('retorno no futuro => retorno_agendado', function () {
    $guest = stageGuest();
    addContact($guest, ContactOutcome::RetornoAgendado, now()->addDay()->toDateTimeString());
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::RETORNO_AGENDADO);
});

it('retorno no passado => retorno_vencido', function () {
    $guest = stageGuest();
    addContact($guest, ContactOutcome::RetornoAgendado, now()->subDay()->toDateTimeString());
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::RETORNO_VENCIDO);
});

it('confirmou/recusou/invalido => respondido', function () {
    foreach ([ContactOutcome::Confirmou, ContactOutcome::Recusou, ContactOutcome::ContatoInvalido] as $outcome) {
        $guest = stageGuest();
        addContact($guest, $outcome);
        expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::RESPONDIDO);
    }
});

it('reconfirmado => reconfirmado', function () {
    $guest = stageGuest();
    addContact($guest, ContactOutcome::Reconfirmado);
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::RECONFIRMADO);
});

it('considera apenas o ultimo contato (mais recente)', function () {
    $guest = stageGuest();
    addContact($guest, ContactOutcome::SemResposta);
    addContact($guest, ContactOutcome::Confirmou); // mais recente
    expect(app(ContactStage::class)->for($guest->fresh()))->toBe(ContactStage::RESPONDIDO);
});

it('expoe contact_stage no payload da lista', function () {
    $event = Event::factory()->create();
    $user = App\Models\User::factory()->create();
    $event->users()->attach($user, ['role' => App\Enums\EventRole::Gestor->value]);
    $guest = Guest::factory()->inEvent($event)->create();
    addContact($guest, ContactOutcome::SemResposta);

    $this->actingAs($user)
        ->get(route('guests.index', $event->id))
        ->assertInertia(fn ($page) => $page
            ->where('guests.data.0.contact_stage', ContactStage::SEM_RESPOSTA)
        );
});
