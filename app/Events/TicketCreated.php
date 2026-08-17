<?php

namespace App\Events;

use App\Models\Ticket;

final class TicketCreated
{
    public function __construct(public readonly Ticket $ticket) {}
}
