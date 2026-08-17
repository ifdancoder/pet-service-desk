<?php

use App\Events\SlaBreached;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCommented;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Models\SlaViolation;
use App\Models\Ticket;
use App\Models\TicketComment;

test('TicketCreated carries the ticket', function () {
    $ticket = Ticket::factory()->create();

    expect((new TicketCreated($ticket))->ticket->is($ticket))->toBeTrue();
});

test('TicketAssigned carries the ticket', function () {
    $ticket = Ticket::factory()->create();

    expect((new TicketAssigned($ticket))->ticket->is($ticket))->toBeTrue();
});

test('TicketClosed carries the ticket', function () {
    $ticket = Ticket::factory()->create();

    expect((new TicketClosed($ticket))->ticket->is($ticket))->toBeTrue();
});

test('TicketReopened carries the ticket', function () {
    $ticket = Ticket::factory()->create();

    expect((new TicketReopened($ticket))->ticket->is($ticket))->toBeTrue();
});

test('TicketCommented carries the comment', function () {
    $comment = TicketComment::factory()->create();

    expect((new TicketCommented($comment))->comment->is($comment))->toBeTrue();
});

test('SlaBreached carries the violation', function () {
    $violation = SlaViolation::factory()->create();

    expect((new SlaBreached($violation))->violation->is($violation))->toBeTrue();
});
