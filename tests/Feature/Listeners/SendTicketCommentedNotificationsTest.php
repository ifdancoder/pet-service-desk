<?php

use App\Events\TicketCommented;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\TicketCommentedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

test('an internal comment notifies staff, not the requester', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $requester = User::factory()->for($department)->create();
    $assignee = User::factory()->for($department)->create();
    $author = User::factory()->for($department)->create();
    Permission::findOrCreate('ticket.view-team');
    Permission::findOrCreate('ticket.view-all');
    $assignee->givePermissionTo('ticket.view-team');
    $author->givePermissionTo('ticket.view-team');

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'department_id' => $department->id,
    ]);

    $comment = TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'author_id' => $author->id,
        'is_internal' => true,
    ]);

    event(new TicketCommented($comment));

    Notification::assertSentTo($assignee, TicketCommentedNotification::class);
    Notification::assertNotSentTo($requester, TicketCommentedNotification::class);
    Notification::assertNotSentTo($author, TicketCommentedNotification::class);
});

test('a public comment notifies the requester and watchers', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $requester = User::factory()->for($department)->create();
    $watcher = User::factory()->create();
    $author = User::factory()->for($department)->create();

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'department_id' => $department->id,
    ]);
    $ticket->watchers()->attach($watcher);

    $comment = TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'author_id' => $author->id,
        'is_internal' => false,
    ]);

    event(new TicketCommented($comment));

    Notification::assertSentTo($requester, TicketCommentedNotification::class);
    Notification::assertSentTo($watcher, TicketCommentedNotification::class);
});

test('the comment author is never notified of their own comment', function () {
    Notification::fake();

    $ticket = Ticket::factory()->create();
    $author = $ticket->requester;

    $comment = TicketComment::factory()->create([
        'ticket_id' => $ticket->id,
        'author_id' => $author->id,
        'is_internal' => false,
    ]);

    event(new TicketCommented($comment));

    Notification::assertNothingSent();
});
