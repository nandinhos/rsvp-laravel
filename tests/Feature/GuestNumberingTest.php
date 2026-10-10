<?php

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Services\GuestNumberAllocator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * CA-13 (parcial) — numeração estável e não reutilizada.
 */
it('aloca numeração sequencial monotônica por evento', function () {
    $event = Event::factory()->create();
    $allocator = new GuestNumberAllocator;

    expect($allocator->next($event))->toBe(1);
    expect($allocator->next($event))->toBe(2);
    expect($allocator->next($event))->toBe(3);
});

it('mantém sequências independentes entre eventos', function () {
    $eventA = Event::factory()->create();
    $eventB = Event::factory()->create();
    $allocator = new GuestNumberAllocator;

    expect($allocator->next($eventA))->toBe(1);
    expect($allocator->next($eventB))->toBe(1);
    expect($allocator->next($eventA))->toBe(2);
    expect($allocator->next($eventB))->toBe(2);
});

it('não reutiliza número após exclusão lógica (CA-13)', function () {
    $event = Event::factory()->create();
    $category = GuestCategory::factory()->for($event)->create();
    $allocator = new GuestNumberAllocator;

    $n1 = $allocator->next($event);
    $guest = Guest::factory()->for($event)->create([
        'number' => $n1,
        'guest_category_id' => $category->id,
    ]);

    $guest->delete(); // soft delete

    $n2 = $allocator->next($event);

    expect($n2)->toBe(2)
        ->and($n2)->not->toBe($n1);
});

/**
 * CA-02 — todo convidado ativo possui categoria válida.
 * A FK NOT NULL guest_category_id garante no nível do banco.
 */
it('exige categoria para o convidado (CA-02)', function () {
    $event = Event::factory()->create();

    expect(fn () => DB::table('guests')->insert([
        'event_id' => $event->id,
        'number' => 1,
        'full_name' => 'Sem Categoria',
        'guest_category_id' => null,
        'rsvp_status' => 'pendente',
        'participation_role' => 'titular',
        'attendance_status' => 'aguardando',
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('garante unicidade de número por evento (CA-13)', function () {
    $event = Event::factory()->create();
    $category = GuestCategory::factory()->for($event)->create();

    Guest::factory()->for($event)->create([
        'number' => 1,
        'guest_category_id' => $category->id,
    ]);

    expect(fn () => Guest::factory()->for($event)->create([
        'number' => 1,
        'guest_category_id' => $category->id,
    ]))->toThrow(QueryException::class);
});
