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
            // team_id is only dehydrated for users holding 'ticket.assign'
            // (see TicketForm); when the key is absent the record's current
            // team must be preserved, not nulled out.
            teamId: array_key_exists('team_id', $data) ? $data['team_id'] : $record->team_id,
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
