<?php

use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketReopened;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketClosedNotification;
use App\Notifications\TicketReopenedNotification;
use Illuminate\Support\Facades\Notification;

test('TicketAssigned notifies the assignee', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    event(new TicketAssigned($ticket));

    Notification::assertSentTo($assignee, TicketAssignedNotification::class);
});

test('TicketClosed notifies the requester', function () {
    Notification::fake();

    $requester = User::factory()->create();
    $ticket = Ticket::factory()->create(['requester_id' => $requester->id]);

    event(new TicketClosed($ticket));

    Notification::assertSentTo($requester, TicketClosedNotification::class);
});

test('TicketReopened notifies the assignee when one exists', function () {
    Notification::fake();

    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    event(new TicketReopened($ticket));

    Notification::assertSentTo($assignee, TicketReopenedNotification::class);
});

test('TicketReopened sends nothing when there is no assignee', function () {
    Notification::fake();

    $ticket = Ticket::factory()->create(['assignee_id' => null]);

    event(new TicketReopened($ticket));

    Notification::assertNothingSent();
});
