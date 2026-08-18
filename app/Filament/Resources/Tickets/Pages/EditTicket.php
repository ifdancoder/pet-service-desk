<?php

namespace App\Filament\Resources\Tickets\Pages;

use App\DataTransferObjects\TicketData;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Services\TicketService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Ticket $record */
        $ticketData = new TicketData(
            subject: $data['subject'],
            description: $data['description'],
            priority: $record->priority,
            categoryId: (int) $data['category_id'],
            departmentId: (int) $data['department_id'],
            teamId: $data['team_id'] ?? null,
            assigneeId: $record->assignee_id,
        );

        return app(TicketService::class)->update($record, $ticketData);
    }

    protected function getHeaderActions(): array
    {
        return [
            TicketResource::assignAction(),
            TicketResource::closeAction(),
            TicketResource::reopenAction(),
            TicketResource::changePriorityAction(),
        ];
    }
}
