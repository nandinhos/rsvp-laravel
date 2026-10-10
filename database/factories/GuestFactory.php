<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Enums\ParticipationRole;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Services\GuestNumberAllocator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        $event = Event::factory();

        return [
            'event_id' => $event,
            'number' => fake()->unique()->numberBetween(1, 100000),
            'full_name' => fake()->name(),
            'guest_category_id' => GuestCategory::factory(),
            'rsvp_status' => RsvpStatus::Pendente,
            'participation_role' => ParticipationRole::Titular,
            'attendance_status' => AttendanceStatus::Aguardando,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['rsvp_status' => RsvpStatus::Confirmado]);
    }

    public function present(): static
    {
        return $this->state(fn () => [
            'attendance_status' => AttendanceStatus::Presente,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Cria o convidado em um evento com categoria pertencente ao MESMO evento,
     * atribuindo numeração sequencial via GuestNumberAllocator.
     */
    public function inEvent(Event $event): static
    {
        return $this->state(function () use ($event) {
            $category = GuestCategory::factory()->for($event)->create();

            return [
                'event_id' => $event->id,
                'guest_category_id' => $category->id,
                'number' => app(GuestNumberAllocator::class)->next($event),
            ];
        });
    }
}
