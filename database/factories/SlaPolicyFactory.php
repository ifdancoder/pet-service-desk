<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => TicketCategory::factory(),
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 1_440,
            'active' => true,
        ];
    }

    public function forPriority(TicketPriority $priority, int $resolutionMinutes): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
            'resolution_time_minutes' => $resolutionMinutes,
        ]);
    }
}
