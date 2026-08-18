<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['ticket.view-all', 'ticket.assign', 'ticket.close', 'ticket.change-priority'] as $permission) {
        Permission::findOrCreate($permission);
    }
    $this->manager = User::factory()->create();
    $this->manager->givePermissionTo(['ticket.view-all', 'ticket.assign', 'ticket.close', 'ticket.change-priority']);
    $this->actingAs($this->manager, 'web');
});

test('bulk assign assigns every selected ticket', function () {
    $tickets = Ticket::factory()->count(3)->create();
    $agent = User::factory()->create();

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkAssign', $tickets, data: ['assignee_id' => $agent->id])
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(fn (Ticket $ticket) => expect($ticket->fresh()->assignee_id)->toBe($agent->id));
});

test('bulk close closes every selected open ticket', function () {
    $tickets = Ticket::factory()->count(3)->create(['status' => TicketStatus::Open]);

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkClose', $tickets)
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(fn (Ticket $ticket) => expect($ticket->fresh()->status)->toBe(TicketStatus::Closed));
});

test('bulk change priority updates every selected ticket', function () {
    $tickets = Ticket::factory()->count(3)->create(['priority' => TicketPriority::Low]);

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkChangePriority', $tickets, data: ['priority' => TicketPriority::High->value])
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(fn (Ticket $ticket) => expect($ticket->fresh()->priority)->toBe(TicketPriority::High));
});
