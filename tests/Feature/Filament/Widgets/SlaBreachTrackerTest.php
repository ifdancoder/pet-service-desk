<?php

use App\Filament\Widgets\SlaBreachTracker;
use App\Models\SlaViolation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('shows breached and soon-to-be-breached tickets visible to the acting user', function () {
    // 'ticket.view-own' rather than 'ticket.view-all': view-all makes
    // Ticket::visibleTo() a no-op, so the widget's scoping would go untested.
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');
    $this->actingAs($user, 'web');

    $breached = Ticket::factory()->create([
        'requester_id' => $user->id,
        'sla_due_at' => Carbon::now()->subHour(),
    ]);
    SlaViolation::factory()->for($breached)->create();

    // Has a violation but a far-future due date — only the violation branch of the
    // query's OR should include this, proving that branch is load-bearing and not
    // redundant with the deadline check.
    $breachedFarFuture = Ticket::factory()->create([
        'requester_id' => $user->id,
        'sla_due_at' => Carbon::now()->addDays(3),
    ]);
    SlaViolation::factory()->for($breachedFarFuture)->create();

    $approaching = Ticket::factory()->create([
        'requester_id' => $user->id,
        'sla_due_at' => Carbon::now()->addHour(),
    ]);

    $safe = Ticket::factory()->create(['requester_id' => $user->id, 'sla_due_at' => Carbon::now()->addDay()]);
    $noDeadline = Ticket::factory()->create(['requester_id' => $user->id, 'sla_due_at' => null]);

    // Breached, but someone else's ticket — visibleTo() must exclude it.
    $someoneElses = Ticket::factory()->create(['sla_due_at' => Carbon::now()->subHour()]);
    SlaViolation::factory()->for($someoneElses)->create();

    // Breached and closed — DetectSlaBreaches scopes to ->open(), so a closed
    // ticket must not linger in the tracker either.
    $closedBreached = Ticket::factory()->closed()->create([
        'requester_id' => $user->id,
        'sla_due_at' => Carbon::now()->subHour(),
    ]);
    SlaViolation::factory()->for($closedBreached)->create();

    Livewire::test(SlaBreachTracker::class)
        ->assertCanSeeTableRecords([$breached, $breachedFarFuture, $approaching])
        ->assertCanNotSeeTableRecords([$safe, $noDeadline, $someoneElses, $closedBreached]);
});
