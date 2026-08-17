<?php

use App\DataTransferObjects\TicketData;
use App\Enums\TicketPriority;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketCreated;
use App\Events\TicketReopened;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Event;

test('creating a ticket dispatches TicketCreated', function () {
    Event::fake();

    $requester = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    $data = new TicketData(
        subject: 'Test',
        description: 'Test description',
        priority: TicketPriority::Normal,
        categoryId: $category->id,
        departmentId: $department->id,
        teamId: null,
        assigneeId: null,
    );

    $ticket = app(TicketService::class)->create($data, $requester);

    Event::assertDispatched(TicketCreated::class, fn ($event) => $event->ticket->is($ticket));
});

test('assigning a ticket dispatches TicketAssigned', function () {
    Event::fake();

    $ticket = Ticket::factory()->create();
    $assignee = User::factory()->create();

    app(TicketService::class)->assign($ticket, $assignee);

    Event::assertDispatched(TicketAssigned::class, fn ($event) => $event->ticket->is($ticket));
});

test('closing a ticket dispatches TicketClosed', function () {
    Event::fake();

    $ticket = Ticket::factory()->create();

    app(TicketService::class)->close($ticket);

    Event::assertDispatched(TicketClosed::class, fn ($event) => $event->ticket->is($ticket));
});

test('reopening a ticket dispatches TicketReopened', function () {
    Event::fake();

    $ticket = Ticket::factory()->closed()->create();

    app(TicketService::class)->reopen($ticket);

    Event::assertDispatched(TicketReopened::class, fn ($event) => $event->ticket->is($ticket));
});
