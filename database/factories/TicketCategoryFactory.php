<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'active' => true,
            'default_priority' => fake()->randomElement(TicketPriority::cases()),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
