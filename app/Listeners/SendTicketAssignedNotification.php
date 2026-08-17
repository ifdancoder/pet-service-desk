<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Support\Facades\Notification;

class SendTicketAssignedNotification
{
    public function handle(TicketAssigned $event): void
    {
        if ($event->ticket->assignee === null) {
            return;
        }

        Notification::send($event->ticket->assignee, new TicketAssignedNotification($event->ticket));
    }
}
