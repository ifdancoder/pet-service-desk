<?php

namespace Database\Factories;

use App\Models\SlaPolicy;
use App\Models\SlaViolation;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaViolation>
 */
class SlaViolationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'sla_policy_id' => SlaPolicy::factory(),
            'breached_at' => now(),
        ];
    }
}
