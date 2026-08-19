<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketClosed;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Event;
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

test('bulk assign assigns every selected ticket through TicketService', function () {
    // The per-record event count is what distinguishes routing each record
    // through TicketService from a single Ticket::whereIn()->update().
    Event::fake();
    $tickets = Ticket::factory()->count(3)->create();
    $agent = User::factory()->create();

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkAssign', $tickets, data: ['assignee_id' => $agent->id])
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(fn (Ticket $ticket) => expect($ticket->fresh()->assignee_id)->toBe($agent->id));
    Event::assertDispatchedTimes(TicketAssigned::class, 3);
});

test('bulk close closes every selected open ticket through TicketService', function () {
    Event::fake();
    $tickets = Ticket::factory()->count(3)->create(['status' => TicketStatus::Open]);

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkClose', $tickets)
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(function (Ticket $ticket) {
        $fresh = $ticket->fresh();
        expect($fresh->status)->toBe(TicketStatus::Closed)
            ->and($fresh->closed_at)->not->toBeNull();
    });
    Event::assertDispatchedTimes(TicketClosed::class, 3);
});

test('bulk change priority updates and recalculates the SLA for every selected ticket', function () {
    // A category-less (default) policy applies to all three tickets regardless
    // of their own factory-made categories.
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::High,
        'resolution_time_minutes' => 777,
        'active' => true,
    ]);

    $tickets = Ticket::factory()->count(3)->create(['priority' => TicketPriority::Low, 'sla_due_at' => null]);

    Livewire::test(ListTickets::class)
        ->callTableBulkAction('bulkChangePriority', $tickets, data: ['priority' => TicketPriority::High->value])
        ->assertHasNoTableBulkActionErrors();

    $tickets->each(function (Ticket $ticket) {
        $fresh = $ticket->fresh();
        // Only TicketService::changePriority() re-runs the SLA calculator; a
        // bulk column UPDATE would leave sla_due_at null.
        expect($fresh->priority)->toBe(TicketPriority::High)
            ->and($fresh->sla_due_at)->not->toBeNull()
            ->and($fresh->sla_due_at->equalTo($ticket->created_at->copy()->addMinutes(777)))->toBeTrue();
    });
});
