<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Support\Facades\Notification;

class SendTicketCreatedNotifications
{
    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        Notification::send($ticket->requester, new TicketCreatedNotification($ticket));

        $staff = $ticket->department->staffUsers()
            ->reject(fn ($user) => $user->id === $ticket->requester_id);

        if ($staff->isNotEmpty()) {
            Notification::send($staff, new TicketCreatedNotification($ticket));
        }
    }
}
