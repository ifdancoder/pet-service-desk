<?php

namespace App\Events;

use App\Models\Ticket;

final class TicketAssigned
{
    public function __construct(public readonly Ticket $ticket) {}
}
