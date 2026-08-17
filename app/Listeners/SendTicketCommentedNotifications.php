<?php

namespace App\Listeners;

use App\Events\TicketCommented;
use App\Models\Ticket;
use App\Notifications\TicketCommentedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class SendTicketCommentedNotifications
{
    public function handle(TicketCommented $event): void
    {
        $comment = $event->comment;
        $ticket = $comment->ticket;

        $recipients = $comment->is_internal
            ? $this->staffRecipients($ticket, $comment->author_id)
            : $this->requesterAndWatchers($ticket, $comment->author_id);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new TicketCommentedNotification($comment));
        }
    }

    private function staffRecipients(Ticket $ticket, int $authorId): Collection
    {
        $recipients = $ticket->department->staffUsers();

        if ($ticket->assignee !== null) {
            $recipients = $recipients->push($ticket->assignee);
        }

        return $recipients->unique('id')->reject(fn ($user) => $user->id === $authorId);
    }

    private function requesterAndWatchers(Ticket $ticket, int $authorId): Collection
    {
        return $ticket->watchers
            ->push($ticket->requester)
            ->unique('id')
            ->reject(fn ($user) => $user->id === $authorId);
    }
}
