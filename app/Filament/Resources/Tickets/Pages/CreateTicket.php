<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\DataTransferObjects\TicketData;
use App\Enums\TicketPriority;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\User;
use App\Services\TicketService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $requester = User::findOrFail($data['requester_id']);

        $ticketData = new TicketData(
            subject: $data['subject'],
            description: $data['description'],
            priority: $data['priority'] instanceof TicketPriority
                ? $data['priority']
                : TicketPriority::from($data['priority']),
            categoryId: (int) $data['category_id'],
            departmentId: (int) $data['department_id'],
            teamId: $data['team_id'] ?? null,
            assigneeId: null,
        );

        return app(TicketService::class)->create($ticketData, $requester);
    }
}
