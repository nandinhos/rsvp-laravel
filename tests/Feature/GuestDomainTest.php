<?php

use App\Enums\ParticipationRole;
use App\Enums\RelationshipType;
use App\Enums\RsvpStatus;
use App\Exceptions\GuestDomainException;
use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestMetrics;
use App\Services\GuestRelationshipService;
use App\Services\RsvpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->event = Event::factory()->create(['estimated_audience' => 200]);
    $this->rsvp = new RsvpService;
    $this->links = new GuestRelationshipService;
});

function guest(Event $event, array $attrs = []): Guest
{
    return Guest::factory()->inEvent($event)->create($attrs);
}

/**
 * CA-03 — o valor 0 importado (=> pendente) não é tratado como recusa.
 */
it('importa flag 0 como pendente e 1 como confirmado, nunca recusa (CA-03)', function () {
    expect(RsvpStatus::fromImportFlag(0))->toBe(RsvpStatus::Pendente);
    expect(RsvpStatus::fromImportFlag('0'))->toBe(RsvpStatus::Pendente);
    expect(RsvpStatus::fromImportFlag(null))->toBe(RsvpStatus::Pendente);
    expect(RsvpStatus::fromImportFlag(1))->toBe(RsvpStatus::Confirmado);
    expect(RsvpStatus::fromImportFlag('1'))->toBe(RsvpStatus::Confirmado);
});

it('recusa é ação explícita e distinta de pendente (CA-03)', function () {
    $g = guest($this->event);
    expect($g->rsvp_status)->toBe(RsvpStatus::Pendente);

    $this->rsvp->decline($g);
    expect($g->fresh()->rsvp_status)->toBe(RsvpStatus::NaoParticipara);
});

/**
 * CA-04 — titular confirmado + acompanhante confirmado = duas pessoas previstas,
 * uma titular e outra acompanhante (acompanhante NÃO é promovido).
 */
it('acompanhante de titular confirmado permanece acompanhante (CA-04)', function () {
    $host = guest($this->event);
    $companion = guest($this->event);

    $this->links->attachCompanion($companion, $host);

    $this->rsvp->confirm($host);       // titular confirma primeiro
    $this->rsvp->confirm($companion);  // depois o acompanhante

    expect($host->fresh()->participation_role)->toBe(ParticipationRole::Titular);
    expect($companion->fresh()->participation_role)->toBe(ParticipationRole::Acompanhante);

    $metrics = new GuestMetrics($this->event->fresh());
    expect($metrics->confirmedHosts())->toBe(1);
    expect($metrics->confirmedCompanions())->toBe(1);
    expect($metrics->confirmedRepresentatives())->toBe(0);
    expect($metrics->expectedParticipants())->toBe(2);
});

/**
 * CA-05 — acompanhante confirmado de titular NÃO confirmado vira representante,
 * preservando a referência ao titular e a origem como acompanhante.
 */
it('promove acompanhante a representante quando titular não confirmado (CA-05)', function () {
    $host = guest($this->event); // pendente
    $companion = guest($this->event);
    $this->links->attachCompanion($companion, $host);

    $this->rsvp->confirm($companion);

    $companion->refresh();
    expect($companion->participation_role)->toBe(ParticipationRole::Representante);

    $link = $companion->hostRelationship()->first();
    expect($link->type)->toBe(RelationshipType::Representante);
    expect($link->origin_type)->toBe(RelationshipType::Acompanhante); // origem preservada
    expect($link->related_guest_id)->toBe($host->id);                  // referência ao titular

    $metrics = new GuestMetrics($this->event->fresh());
    expect($metrics->confirmedRepresentatives())->toBe(1);
    expect($metrics->confirmedCompanions())->toBe(0); // não conta como acompanhante (CA-07)
});

/**
 * CA-06 — confirmação posterior do titular representado exige resolução
 * explícita; cria conflito bloqueante sem alterar registros silenciosamente.
 */
it('marca conflito bloqueante quando titular representado confirma depois (CA-06)', function () {
    $host = guest($this->event); // pendente
    $companion = guest($this->event);
    $this->links->attachCompanion($companion, $host);

    $this->rsvp->confirm($companion); // promovido a representante
    $link = $companion->hostRelationship()->first();
    expect($link->is_conflict)->toBeFalse();

    // Agora o titular confirma: surge o conflito.
    $this->rsvp->confirm($host);

    $link->refresh();
    expect($link->is_conflict)->toBeTrue();
    expect($link->resolved_at)->toBeNull();

    // Nada foi alterado silenciosamente: representante continua representante.
    expect($companion->fresh()->participation_role)->toBe(ParticipationRole::Representante);

    $metrics = new GuestMetrics($this->event->fresh());
    expect($metrics->hasPendingConflicts())->toBeTrue();
});

/**
 * CA-07 — nenhuma pessoa é contada duas vezes.
 */
it('não conta a mesma pessoa em dois indicadores (CA-07)', function () {
    $host = guest($this->event);
    $companion = guest($this->event);
    $this->links->attachCompanion($companion, $host);
    $this->rsvp->confirm($companion); // vira representante

    $metrics = new GuestMetrics($this->event->fresh());

    $soma = $metrics->confirmedHosts()
        + $metrics->confirmedCompanions()
        + $metrics->confirmedRepresentatives();

    expect($soma)->toBe($metrics->expectedParticipants());
});

// --- Validações estruturais do vínculo (PRD §2.5) ---

it('impede representar a si mesmo', function () {
    $g = guest($this->event);
    expect(fn () => $this->links->attachRepresentative($g, $g))
        ->toThrow(GuestDomainException::class);
});

it('impede substituição circular', function () {
    $a = guest($this->event);
    $b = guest($this->event);
    $this->links->attachRepresentative($b, $a); // b representa a

    expect(fn () => $this->links->attachRepresentative($a, $b)) // a representa b => ciclo
        ->toThrow(GuestDomainException::class);
});

it('impede vínculo entre eventos diferentes (CA-01)', function () {
    $outro = Event::factory()->create();
    $a = guest($this->event);
    $b = guest($outro);

    expect(fn () => $this->links->attachCompanion($a, $b))
        ->toThrow(GuestDomainException::class);
});

it('impede múltiplos vínculos ativos para o mesmo dependente', function () {
    $host1 = guest($this->event);
    $host2 = guest($this->event);
    $dep = guest($this->event);

    $this->links->attachCompanion($dep, $host1);

    expect(fn () => $this->links->attachCompanion($dep, $host2))
        ->toThrow(GuestDomainException::class);
});

it('impede múltiplos representantes ativos para o mesmo titular (§2.5)', function () {
    $host = guest($this->event);
    $rep1 = guest($this->event);
    $rep2 = guest($this->event);

    $this->links->attachRepresentative($rep1, $host);

    expect(fn () => $this->links->attachRepresentative($rep2, $host))
        ->toThrow(GuestDomainException::class);
});

it('permite múltiplos acompanhantes para o mesmo titular', function () {
    $host = guest($this->event);
    $c1 = guest($this->event);
    $c2 = guest($this->event);

    $this->links->attachCompanion($c1, $host);
    $this->links->attachCompanion($c2, $host);

    expect($host->fresh()->dependents()->count())->toBe(2);
});

it('taxa de confirmação é indisponível quando público estimado é zero', function () {
    $zero = Event::factory()->create(['estimated_audience' => 0]);
    $metrics = new GuestMetrics($zero);
    expect($metrics->confirmationRate())->toBeNull();
});
