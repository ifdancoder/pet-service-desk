<?php

namespace App\Notifications;

use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketCommentedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TicketComment $comment) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New comment on ticket #{$this->comment->ticket_id}")
            ->line('A new comment was added: "'.Str::limit($this->comment->body, 100).'"');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->comment->ticket_id,
            'comment_id' => $this->comment->id,
            'message' => "New comment on ticket #{$this->comment->ticket_id}",
        ];
    }
}
