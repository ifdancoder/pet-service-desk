<?php

namespace App\Notifications;

use App\Models\SlaViolation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SlaViolation $violation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SLA breached: ticket #{$this->violation->ticket_id}")
            ->line("Ticket \"{$this->violation->ticket->subject}\" has breached its SLA deadline.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->violation->ticket_id,
            'violation_id' => $this->violation->id,
            'message' => "SLA breached: ticket #{$this->violation->ticket_id}",
        ];
    }
}
