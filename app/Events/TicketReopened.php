<?php

namespace App\Events;

use App\Models\Ticket;

final class TicketReopened
{
    public function __construct(public readonly Ticket $ticket) {}
}
