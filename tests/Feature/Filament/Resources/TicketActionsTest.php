<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketReopened;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Deviation from the brief: TicketResource::canEdit() (Task 8) requires the
    // 'ticket.manage' permission to enter the Edit page at all — without it, mounting
    // EditTicket 403s and any subsequent ->callAction() fails with a cryptic
    // "Attempt to read property ... on null" error instead of a clear assertion
    // failure. Added 'ticket.manage' alongside the brief's listed permissions so the
    // manager can actually reach the page these actions are registered on.
    foreach (['ticket.view-all', 'ticket.assign', 'ticket.close', 'ticket.reopen', 'ticket.change-priority', 'ticket.manage'] as $permission) {
        Permission::findOrCreate($permission);
    }
    $this->manager = User::factory()->create();
    $this->manager->givePermissionTo(['ticket.view-all', 'ticket.assign', 'ticket.close', 'ticket.reopen', 'ticket.change-priority', 'ticket.manage']);
    $this->actingAs($this->manager, 'web');
});

test('assign action calls TicketService and dispatches TicketAssigned', function () {
    Event::fake();
    $ticket = Ticket::factory()->create();
    $agent = User::factory()->create();

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('assign', data: ['assignee_id' => $agent->id])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->assignee_id)->toBe($agent->id);
    Event::assertDispatched(TicketAssigned::class, fn ($event) => $event->ticket->is($ticket));
});

test('close action calls TicketService and dispatches TicketClosed', function () {
    Event::fake();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('close')
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
    Event::assertDispatched(TicketClosed::class, fn ($event) => $event->ticket->is($ticket));
});

test('reopen action calls TicketService and dispatches TicketReopened', function () {
    Event::fake();
    $ticket = Ticket::factory()->closed()->create();

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('reopen')
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
    Event::assertDispatched(TicketReopened::class, fn ($event) => $event->ticket->is($ticket));
});

test('change priority action calls TicketService and recalculates sla_due_at', function () {
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('changePriority', data: ['priority' => TicketPriority::Critical->value])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Critical);
});
