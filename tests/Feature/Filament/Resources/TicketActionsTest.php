<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Events\TicketReopened;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // TicketResource::canEdit() requires 'ticket.manage' just to enter the Edit
    // page. Without it, mounting EditTicket 403s and any subsequent
    // ->callAction() fails with a cryptic "Attempt to read property ... on
    // null" error instead of a clear assertion failure, so it's granted here
    // alongside the per-action permissions each test actually exercises.
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

    // Driven from the list table, not the Edit page: TicketResource::canEdit()
    // refuses closed tickets (mirroring TicketPolicy::update()'s guard), so the
    // Edit page is unreachable for a closed ticket by design. The same
    // reopenAction() is registered as a table record action.
    Livewire::test(ListTickets::class)
        ->callTableAction('reopen', $ticket)
        ->assertHasNoTableActionErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);
    Event::assertDispatched(TicketReopened::class, fn ($event) => $event->ticket->is($ticket));
});

test('a team_lead can list team tickets and use the assign action', function () {
    // Regression: team_lead held no ticket.view-* permission, so TicketPolicy::viewAny()
    // failed and the whole Tickets resource 403'd, making their ticket.assign
    // grant unreachable in the panel.
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    Event::fake();

    $department = Department::factory()->create();
    $lead = User::factory()->for($department)->create();
    $lead->assignRole(UserRole::TeamLead->value);
    $team = Team::factory()->for($department)->create();
    $team->users()->attach($lead);
    $this->actingAs($lead, 'web');

    $ticket = Ticket::factory()->create(['team_id' => $team->id, 'department_id' => $department->id]);
    $otherTicket = Ticket::factory()->create();
    $agent = User::factory()->create();

    Livewire::test(ListTickets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$ticket])
        ->assertCanNotSeeTableRecords([$otherTicket]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('assign', data: ['assignee_id' => $agent->id])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->assignee_id)->toBe($agent->id);
    Event::assertDispatched(TicketAssigned::class, fn ($event) => $event->ticket->is($ticket));
});

test('change priority action calls TicketService and recalculates sla_due_at', function () {
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction('changePriority', data: ['priority' => TicketPriority::Critical->value])
        ->assertHasNoActionErrors();

    expect($ticket->fresh()->priority)->toBe(TicketPriority::Critical);
});
