<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'category_id' => TicketCategory::factory(),
            'department_id' => Department::factory(),
            'team_id' => null,
            'subject' => fake()->sentence(6),
            'description' => fake()->paragraphs(2, true),
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Normal,
            'sla_due_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    public function priority(TicketPriority $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
