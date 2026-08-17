<?php

namespace App\Listeners;

use App\Events\TicketClosed;
use App\Notifications\TicketClosedNotification;
use Illuminate\Support\Facades\Notification;

class SendTicketClosedNotification
{
    public function handle(TicketClosed $event): void
    {
        Notification::send($event->ticket->requester, new TicketClosedNotification($event->ticket));
    }
}
