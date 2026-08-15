<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentPolicy
{
    public function create(User $user, Ticket $ticket): bool
    {
        return $user->can('comment.create') && $user->can('view', $ticket);
    }

    public function delete(User $user, TicketComment $comment): bool
    {
        if ($user->can('comment.delete-any')) {
            return true;
        }

        return $user->can('comment.delete-own') && $comment->author_id === $user->id;
    }
}
