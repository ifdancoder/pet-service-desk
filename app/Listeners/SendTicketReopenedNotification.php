<?php

namespace App\Listeners;

use App\Events\TicketReopened;
use App\Notifications\TicketReopenedNotification;
use Illuminate\Support\Facades\Notification;

class SendTicketReopenedNotification
{
    public function handle(TicketReopened $event): void
    {
        if ($event->ticket->assignee === null) {
            return;
        }

        Notification::send($event->ticket->assignee, new TicketReopenedNotification($event->ticket));
    }
}
