<?php

namespace App\Services;

use App\DataTransferObjects\TicketCommentData;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentService
{
    public function create(Ticket $ticket, TicketCommentData $data, User $author): TicketComment
    {
        return $ticket->comments()->create([
            'author_id' => $author->id,
            'body' => $data->body,
            'is_internal' => $data->isInternal,
        ]);
    }

    public function delete(TicketComment $comment): void
    {
        $comment->delete();
    }
}
