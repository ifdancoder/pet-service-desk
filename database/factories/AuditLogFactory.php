<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'auditable_type' => Ticket::class,
            'auditable_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'changes' => [
                'subject' => ['old' => 'Old subject', 'new' => 'New subject'],
            ],
        ];
    }
}
