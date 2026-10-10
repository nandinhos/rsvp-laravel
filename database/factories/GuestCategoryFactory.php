<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\GuestCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestCategory>
 */
class GuestCategoryFactory extends Factory
{
    protected $model = GuestCategory::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->word(),
            'sort_order' => 0,
        ];
    }
}
