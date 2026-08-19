<?php

use App\Jobs\IndexTicketInSearch;
use App\Jobs\RemoveTicketFromSearch;
use App\Models\Ticket;
use Illuminate\Support\Facades\Queue;

test('creating a ticket dispatches IndexTicketInSearch', function () {
    Queue::fake();

    $ticket = Ticket::factory()->create();

    Queue::assertPushed(IndexTicketInSearch::class, fn ($job) => $job->ticket->is($ticket));
});

test('updating a ticket dispatches IndexTicketInSearch again', function () {
    $ticket = Ticket::factory()->create();

    Queue::fake();
    $ticket->update(['subject' => 'Updated subject']);

    Queue::assertPushed(IndexTicketInSearch::class, fn ($job) => $job->ticket->is($ticket));
});

test('deleting a ticket dispatches RemoveTicketFromSearch', function () {
    $ticket = Ticket::factory()->create();

    Queue::fake();
    $ticket->delete();

    Queue::assertPushed(RemoveTicketFromSearch::class, fn ($job) => $job->ticketId === $ticket->id);
});
