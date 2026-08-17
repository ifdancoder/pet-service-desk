<?php

use App\Models\SlaViolation;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\SlaBreachedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketClosedNotification;
use App\Notifications\TicketCommentedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketReopenedNotification;
use Illuminate\Notifications\Messages\MailMessage;

test('TicketCreatedNotification is mail and database, with a matching payload', function () {
    $ticket = Ticket::factory()->create();
    $notification = new TicketCreatedNotification($ticket);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toMail($notifiable))->toBeInstanceOf(MailMessage::class)
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($ticket->id);
});

test('TicketAssignedNotification is mail and database, with a matching payload', function () {
    $ticket = Ticket::factory()->create();
    $notification = new TicketAssignedNotification($ticket);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($ticket->id);
});

test('TicketClosedNotification is mail and database, with a matching payload', function () {
    $ticket = Ticket::factory()->create();
    $notification = new TicketClosedNotification($ticket);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($ticket->id);
});

test('TicketReopenedNotification is mail and database, with a matching payload', function () {
    $ticket = Ticket::factory()->create();
    $notification = new TicketReopenedNotification($ticket);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($ticket->id);
});

test('TicketCommentedNotification is mail and database, with a matching payload', function () {
    $comment = TicketComment::factory()->create();
    $notification = new TicketCommentedNotification($comment);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($comment->ticket_id)
        ->and($notification->toArray($notifiable)['comment_id'])->toBe($comment->id);
});

test('SlaBreachedNotification is mail and database, with a matching payload', function () {
    $violation = SlaViolation::factory()->create();
    $notification = new SlaBreachedNotification($violation);
    $notifiable = User::factory()->make();

    expect($notification->via($notifiable))->toBe(['mail', 'database'])
        ->and($notification->toArray($notifiable)['ticket_id'])->toBe($violation->ticket_id)
        ->and($notification->toArray($notifiable)['violation_id'])->toBe($violation->id);
});
