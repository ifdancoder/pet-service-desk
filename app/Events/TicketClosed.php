<?php

namespace App\Events;

use App\Models\Ticket;

final class TicketClosed
{
    public function __construct(public readonly Ticket $ticket) {}
}
