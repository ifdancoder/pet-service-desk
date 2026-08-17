<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view-own')
            || $user->can('ticket.view-team')
            || $user->can('ticket.view-all');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can('ticket.view-all')) {
            return true;
        }

        if (
            $user->can('ticket.view-team')
            && $ticket->team_id !== null
            && $user->teams->contains($ticket->team_id)
        ) {
            return true;
        }

        return $user->can('ticket.view-own') && $ticket->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('ticket.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.update-own')
            && $ticket->requester_id === $user->id
            && ! $ticket->status->isClosed();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.assign')
            && ! $ticket->status->isClosed()
            && $this->view($user, $ticket);
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.change-priority')
            && ! $ticket->status->isClosed()
            && $this->view($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.close')
            && ! $ticket->status->isClosed()
            && $this->view($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.reopen')
            && $ticket->status->isClosed()
            && $this->view($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('ticket.delete');
    }
}
