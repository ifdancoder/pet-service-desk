<?php

namespace App\Services;

use App\DataTransferObjects\TicketData;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaCalculator;

class TicketService
{
    public function __construct(private readonly SlaCalculator $slaCalculator) {}

    public function create(TicketData $data, User $requester): Ticket
    {
        $ticket = Ticket::create([
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

        $ticket->update(['sla_due_at' => $this->slaCalculator->calculate($ticket)]);

        event(new TicketCreated($ticket));

        return $ticket;
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

    public function assign(Ticket $ticket, User $assignee): Ticket
    {
        $ticket->update(['assignee_id' => $assignee->id]);

        event(new TicketAssigned($ticket));

        return $ticket;
    }

    public function changePriority(Ticket $ticket, TicketPriority $priority): Ticket
    {
        $ticket->update(['priority' => $priority]);

        $ticket->update(['sla_due_at' => $this->slaCalculator->calculate($ticket)]);

        return $ticket;
    }

    public function close(Ticket $ticket): Ticket
    {
        $ticket->update(['status' => TicketStatus::Closed, 'closed_at' => now()]);

        event(new TicketClosed($ticket));

        return $ticket;
    }

    public function reopen(Ticket $ticket): Ticket
    {
        $ticket->update(['status' => TicketStatus::Open, 'closed_at' => null]);

        event(new TicketReopened($ticket));

        return $ticket;
    }

    public function attachWatcher(Ticket $ticket, User $watcher): void
    {
        $ticket->watchers()->syncWithoutDetaching($watcher);
    }

    public function detachWatcher(Ticket $ticket, User $watcher): void
    {
        $ticket->watchers()->detach($watcher);
    }

    public function syncTags(Ticket $ticket, array $tagSlugs): Ticket
    {
        $tagIds = Tag::query()->whereIn('slug', $tagSlugs)->pluck('id');
        $ticket->tags()->sync($tagIds);

        return $ticket->fresh();
    }
}
