<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'starts_at' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'location' => fake()->city(),
            'visibility' => EventVisibility::Reservado,
            'estimated_audience' => fake()->numberBetween(50, 500),
            'status' => EventStatus::Ativo,
            'timezone' => 'America/Sao_Paulo',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => EventStatus::Encerrado,
            'closed_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Rascunho]);
    }
}
