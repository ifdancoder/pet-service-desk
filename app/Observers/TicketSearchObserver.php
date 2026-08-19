<?php

namespace App\Observers;

use App\Jobs\IndexTicketInSearch;
use App\Jobs\RemoveTicketFromSearch;
use App\Models\Ticket;

class TicketSearchObserver
{
    public function saved(Ticket $ticket): void
    {
        IndexTicketInSearch::dispatch($ticket);
    }

    public function deleted(Ticket $ticket): void
    {
        RemoveTicketFromSearch::dispatch($ticket->id);
    }
}
