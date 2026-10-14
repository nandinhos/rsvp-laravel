<?php

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestContact;
use App\Services\RetentionPurger;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function purgeContact(Guest $guest): void
{
    GuestContact::create([
        'event_id' => $guest->event_id,
        'guest_id' => $guest->id,
        'channel' => ContactChannel::Ligacao,
        'outcome' => ContactOutcome::SemResposta,
    ]);
}

it('anonimiza convidados de evento encerrado alem da retencao', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Encerrado,
        'closed_at' => now()->subDays(40),
        'retention_days' => 30,
    ]);
    $guest = Guest::factory()->inEvent($event)->create(['full_name' => 'Fulano Real', 'note' => 'sensivel']);
    purgeContact($guest);

    $result = app(RetentionPurger::class)->purge();

    expect($result['events'])->toBe(1);
    $guest->refresh();
    expect($guest->full_name)->not->toBe('Fulano Real');
    expect($guest->full_name)->toContain('anonimizado');
    expect($guest->note)->toBeNull();
    expect(GuestContact::where('guest_id', $guest->id)->count())->toBe(0);
});

it('preserva evento encerrado DENTRO do prazo de retencao', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Encerrado,
        'closed_at' => now()->subDays(10),
        'retention_days' => 30,
    ]);
    $guest = Guest::factory()->inEvent($event)->create(['full_name' => 'Preservado']);

    $result = app(RetentionPurger::class)->purge();

    expect($result['events'])->toBe(0);
    expect($guest->fresh()->full_name)->toBe('Preservado');
});

it('ignora evento sem retention_days definido', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Encerrado,
        'closed_at' => now()->subYears(5),
        'retention_days' => null,
    ]);
    $guest = Guest::factory()->inEvent($event)->create(['full_name' => 'Sem Retencao']);

    app(RetentionPurger::class)->purge();

    expect($guest->fresh()->full_name)->toBe('Sem Retencao');
});

it('ignora evento nao encerrado', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Ativo,
        'closed_at' => null,
        'retention_days' => 30,
    ]);
    $guest = Guest::factory()->inEvent($event)->create(['full_name' => 'Ativo']);

    app(RetentionPurger::class)->purge();

    expect($guest->fresh()->full_name)->toBe('Ativo');
});

it('comando guests:purge executa com sucesso', function () {
    $this->artisan('guests:purge')->assertSuccessful();
});
