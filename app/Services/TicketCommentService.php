<?php

namespace App\Services;

use App\DataTransferObjects\TicketCommentData;
use App\Events\TicketCommented;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentService
{
    public function create(Ticket $ticket, TicketCommentData $data, User $author): TicketComment
    {
        $comment = $ticket->comments()->create([
            'author_id' => $author->id,
            'body' => $data->body,
            'is_internal' => $data->isInternal,
        ]);

        event(new TicketCommented($comment));

        return $comment;
    }

    public function delete(TicketComment $comment): void
    {
        $comment->delete();
    }
}
