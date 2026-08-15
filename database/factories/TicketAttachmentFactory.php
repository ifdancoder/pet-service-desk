<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAttachment>
 */
class TicketAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'ticket_id' => Ticket::factory(),
            'uploader_id' => User::factory(),
            'disk' => 'local',
            'path' => "ticket-attachments/{$name}.pdf",
            'original_name' => "{$name}.pdf",
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_024, 5_242_880),
        ];
    }
}
