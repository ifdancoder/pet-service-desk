<?php

namespace App\Events;

use App\Models\TicketComment;

final class TicketCommented
{
    public function __construct(public readonly TicketComment $comment) {}
}
