<?php

use App\Enums\EventRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use App\Services\Export\GuestExcelExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

function exportMember(Event $event, EventRole $role): User
{
    $user = User::factory()->create();
    $event->users()->attach($user, ['role' => $role->value]);

    return $user;
}

/**
 * CA-11 — permissões verificadas no servidor.
 */
it('nega exportação a operador e leitor (CA-11)', function () {
    $event = Event::factory()->create();
    $operador = exportMember($event, EventRole::Operador);
    $leitor = exportMember($event, EventRole::Leitor);

    $this->actingAs($operador)->get(route('guests.export.excel', $event->id))->assertForbidden();
    $this->actingAs($leitor)->get(route('guests.export.pdf', $event->id))->assertForbidden();
});

it('nega exportação a quem não pertence ao evento (CA-01, CA-11)', function () {
    $event = Event::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->get(route('guests.export.excel', $event->id))->assertForbidden();
});

it('permite exportação Excel a gestor e retorna xlsx', function () {
    $event = Event::factory()->create();
    $gestor = exportMember($event, EventRole::Gestor);
    Guest::factory()->inEvent($event)->create(['full_name' => 'Ana']);

    $this->actingAs($gestor)
        ->get(route('guests.export.excel', $event->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('permite exportação PDF a administrador e retorna pdf', function () {
    $event = Event::factory()->create();
    $admin = exportMember($event, EventRole::Administrador);
    Guest::factory()->inEvent($event)->create();

    $response = $this->actingAs($admin)->get(route('guests.export.pdf', $event->id));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

/**
 * CA-11 — a exportação respeita os filtros solicitados.
 */
it('respeita filtros na exportação Excel', function () {
    $event = Event::factory()->create();
    $gestor = exportMember($event, EventRole::Gestor);
    Guest::factory()->inEvent($event)->create(['full_name' => 'Confirmada', 'rsvp_status' => RsvpStatus::Confirmado]);
    Guest::factory()->inEvent($event)->create(['full_name' => 'Pendente']);

    $exporter = app(GuestExcelExporter::class);
    $path = $exporter->export($event, ['rsvp' => 'confirmado']);

    $sheet = IOFactory::load($path)->getActiveSheet();
    $names = [];
    foreach ($sheet->toArray() as $i => $row) {
        if ($i === 0) {
            continue;
        } // header
        if ($row[1]) {
            $names[] = $row[1];
        }
    }
    unlink($path);

    expect($names)->toBe(['Confirmada']);
});

/**
 * CA-11 — guarda contra injeção de fórmula no Excel.
 */
it('neutraliza injeção de fórmula no Excel', function () {
    $event = Event::factory()->create();
    $category = GuestCategory::factory()->for($event)->create();
    // Nome malicioso que o Excel interpretaria como fórmula.
    Guest::factory()->for($event)->create([
        'number' => 1,
        'guest_category_id' => $category->id,
        'full_name' => '=HYPERLINK("http://evil")',
    ]);

    $path = app(GuestExcelExporter::class)->export($event, []);
    $sheet = IOFactory::load($path)->getActiveSheet();

    // A célula do nome (coluna B, linha 2) deve ter sido prefixada com apóstrofo
    // e NÃO ser interpretada como fórmula.
    $cell = $sheet->getCell('B2');
    expect($cell->isFormula())->toBeFalse();
    expect($cell->getValue())->toStartWith("'=");

    unlink($path);
});
