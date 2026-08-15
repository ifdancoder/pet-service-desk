<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function isClosed(): bool
    {
        return $this === self::Closed;
    }
}
