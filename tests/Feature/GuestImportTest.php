<?php

use App\Enums\EventRole;
use App\Enums\ParticipationRole;
use App\Enums\RelationshipType;
use App\Enums\RsvpStatus;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\GuestCategory;
use App\Models\User;
use App\Services\Import\ImportAnalyzer;
use App\Services\Import\ImportParser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function importEvent(): Event
{
    $event = Event::factory()->create();
    foreach (Event::DEFAULT_CATEGORIES as $i => $name) {
        GuestCategory::factory()->for($event)->create(['name' => $name, 'sort_order' => $i]);
    }

    return $event;
}

function importer(Event $event): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => EventRole::Gestor->value]);

    return $user;
}

// --- Parser (CA-08: respeita aspas) ---

it('faz parsing de registros separados por ; e por nova linha', function () {
    $parser = new ImportParser;
    $records = $parser->parse('Ana,Autoridade,,0;Bruno,Comitê,Ana,1');

    expect($records)->toHaveCount(2);
    expect($records[0])->toBe(['Ana', 'Autoridade', '', '0']);
    expect($records[1])->toBe(['Bruno', 'Comitê', 'Ana', '1']);
});

it('respeita vírgulas dentro de aspas (CA-08)', function () {
    $parser = new ImportParser;
    $records = $parser->parse('"Silva, Ana Maria",Autoridade,,1');

    expect($records)->toHaveCount(1);
    expect($records[0][0])->toBe('Silva, Ana Maria');
    expect($records[0][1])->toBe('Autoridade');
});

it('trata aspas escapadas por duplicação', function () {
    $parser = new ImportParser;
    $records = $parser->parse('"Ana ""A"" Silva",Comitê,,0');

    expect($records[0][0])->toBe('Ana "A" Silva');
});

// --- Analyzer (validação, vínculos, ambiguidade) ---

it('marca erro quando categoria não existe no evento', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([['Ana', 'Inexistente', '', '0']]);

    expect($rows[0]->isValid())->toBeFalse();
    expect($rows[0]->errors)->toContain('Categoria "Inexistente" não existe neste evento.');
});

it('marca erro quando nome é obrigatório ausente', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([['', 'Autoridade', '', '0']]);

    expect($rows[0]->isValid())->toBeFalse();
});

it('resolve vínculo válido dentro do lote', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([
        ['Ana Silva', 'Autoridade', '', '0'],
        ['Bruno Costa', 'Convidado geral', 'Ana Silva', '1'],
    ]);

    expect($rows[0]->isValid())->toBeTrue();
    expect($rows[1]->isValid())->toBeTrue();
});

it('marca erro em relacionamento inexistente (CA-08)', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([
        ['Bruno', 'Convidado geral', 'Fantasma', '1'],
    ]);

    expect($rows[0]->isValid())->toBeFalse();
    expect(implode(' ', $rows[0]->errors))->toContain('não encontrado');
});

it('marca erro em relacionamento ambíguo (CA-08)', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([
        ['Ana', 'Autoridade', '', '0'],
        ['Ana', 'Diretoria', '', '0'],       // dois "Ana"
        ['Bruno', 'Comitê', 'Ana', '1'],      // vínculo ambíguo
    ]);

    expect($rows[2]->isValid())->toBeFalse();
    expect(implode(' ', $rows[2]->errors))->toContain('ambíguo');
});

it('avisa duplicidade sem bloquear (nomes iguais permitidos §14.4)', function () {
    $event = importEvent();
    $rows = (new ImportAnalyzer($event))->analyze([
        ['Ana', 'Autoridade', '', '0'],
        ['Ana', 'Diretoria', '', '0'],
    ]);

    expect($rows[0]->isValid())->toBeTrue();
    expect($rows[1]->warnings)->not->toBeEmpty();
});

// --- Fluxo HTTP ---

it('preview não persiste nada (CA-08)', function () {
    $event = importEvent();
    $gestor = importer($event);

    $this->actingAs($gestor)
        ->post(route('guests.import.preview', $event->id), [
            'text' => 'Ana Silva,Autoridade,,0;Bruno Costa,Convidado geral,Ana Silva,1',
        ])
        ->assertRedirect();

    expect($event->guests()->count())->toBe(0);
});

it('persiste o exemplo do PRD e aplica regra de representação (Ana/Bruno)', function () {
    $event = importEvent();
    $gestor = importer($event);

    // Ana não confirma (0); Bruno é acompanhante de Ana e confirma (1)
    // => Bruno deve ser promovido a representante (CA-05).
    $records = [
        ['Ana Silva', 'Autoridade', '', '0'],
        ['Bruno Costa', 'Convidado geral', 'Ana Silva', '1'],
    ];

    $this->actingAs($gestor)
        ->post(route('guests.import.store', $event->id), ['records' => $records])
        ->assertRedirect(route('guests.index', $event->id));

    expect($event->guests()->count())->toBe(2);

    $ana = $event->guests()->where('full_name', 'Ana Silva')->first();
    $bruno = $event->guests()->where('full_name', 'Bruno Costa')->first();

    expect($ana->rsvp_status)->toBe(RsvpStatus::Pendente);          // 0 => pendente (CA-03)
    expect($bruno->rsvp_status)->toBe(RsvpStatus::Confirmado);      // 1 => confirmado
    expect($bruno->participation_role)->toBe(ParticipationRole::Representante); // promovido (CA-05)

    $link = $bruno->hostRelationship()->first();
    expect($link->origin_type)->toBe(RelationshipType::Acompanhante); // origem preservada
    expect($link->related_guest_id)->toBe($ana->id);
});

it('store ignora registros inválidos e persiste apenas válidos', function () {
    $event = importEvent();
    $gestor = importer($event);

    $records = [
        ['Valida', 'Autoridade', '', '0'],
        ['SemCategoria', 'Inexistente', '', '0'], // inválida
    ];

    $this->actingAs($gestor)
        ->post(route('guests.import.store', $event->id), ['records' => $records]);

    expect($event->guests()->count())->toBe(1);
    expect($event->guests()->first()->full_name)->toBe('Valida');
});

it('registra auditoria da importação (CA-10)', function () {
    $event = importEvent();
    $gestor = importer($event);

    $this->actingAs($gestor)
        ->post(route('guests.import.store', $event->id), [
            'records' => [['Ana', 'Autoridade', '', '0']],
        ]);

    $log = AuditLog::where('action', 'guests.imported')->first();
    expect($log)->not->toBeNull();
    expect($log->event_id)->toBe($event->id);
    expect($log->user_id)->toBe($gestor->id);
});

it('nega importação a quem não pode gerenciar convidados (CA-01)', function () {
    $event = importEvent();
    $leitor = User::factory()->create();
    $event->users()->attach($leitor, ['role' => EventRole::Leitor->value]);

    $this->actingAs($leitor)
        ->post(route('guests.import.store', $event->id), [
            'records' => [['Ana', 'Autoridade', '', '0']],
        ])
        ->assertForbidden();
});
