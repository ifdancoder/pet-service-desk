<?php

use App\Filament\Widgets\SlaBreachTracker;
use App\Models\SlaViolation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('shows breached and soon-to-be-breached tickets visible to the acting user', function () {
    Permission::findOrCreate('ticket.view-all');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-all');
    $this->actingAs($user, 'web');

    $breached = Ticket::factory()->create(['sla_due_at' => Carbon::now()->subHour()]);
    SlaViolation::factory()->for($breached)->create();

    $approaching = Ticket::factory()->create(['sla_due_at' => Carbon::now()->addHour()]);

    $safe = Ticket::factory()->create(['sla_due_at' => Carbon::now()->addDay()]);
    $noDeadline = Ticket::factory()->create(['sla_due_at' => null]);

    Livewire::test(SlaBreachTracker::class)
        ->assertCanSeeTableRecords([$breached, $approaching])
        ->assertCanNotSeeTableRecords([$safe, $noDeadline]);
});
