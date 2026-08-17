<?php

namespace App\Listeners;

use App\Events\SlaBreached;
use App\Notifications\SlaBreachedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class SendSlaBreachedNotification
{
    public function handle(SlaBreached $event): void
    {
        if (! Role::query()->where('name', 'team_lead')->exists()) {
            return;
        }

        $ticket = $event->violation->ticket;

        $teamLeads = $ticket->team_id !== null
            ? $ticket->team->users()->role('team_lead')->get()
            : $ticket->department->users()->role('team_lead')->get();

        if ($teamLeads->isNotEmpty()) {
            Notification::send($teamLeads, new SlaBreachedNotification($event->violation));
        }
    }
}
