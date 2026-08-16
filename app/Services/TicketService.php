<?php

namespace App\Services;

use App\DataTransferObjects\TicketData;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;

class TicketService
{
    public function create(TicketData $data, User $requester): Ticket
    {
        return Ticket::create([
            'requester_id' => $requester->id,
            'assignee_id' => $data->assigneeId,
            'category_id' => $data->categoryId,
            'department_id' => $data->departmentId,
            'team_id' => $data->teamId,
            'subject' => $data->subject,
            'description' => $data->description,
            'status' => TicketStatus::Open,
            'priority' => $data->priority,
        ]);
    }

    public function update(Ticket $ticket, TicketData $data): Ticket
    {
        $ticket->update([
            'assignee_id' => $data->assigneeId,
            'category_id' => $data->categoryId,
            'department_id' => $data->departmentId,
            'team_id' => $data->teamId,
            'subject' => $data->subject,
            'description' => $data->description,
            'priority' => $data->priority,
        ]);

        return $ticket;
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }
}
