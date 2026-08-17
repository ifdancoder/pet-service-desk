<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\SlaBreached;
use App\Notifications\SlaBreachedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

class SendSlaBreachedNotification
{
    public function handle(SlaBreached $event): void
    {
        if (! Role::query()->where('name', UserRole::TeamLead->value)->where('guard_name', config('auth.defaults.guard'))->exists()) {
            return;
        }

        $ticket = $event->violation->ticket;

        $teamLeads = $ticket->team_id !== null
            ? $ticket->team->users()->role(UserRole::TeamLead->value)->get()
            : $ticket->department->users()->role(UserRole::TeamLead->value)->get();

        if ($teamLeads->isNotEmpty()) {
            Notification::send($teamLeads, new SlaBreachedNotification($event->violation));
        }
    }
}
