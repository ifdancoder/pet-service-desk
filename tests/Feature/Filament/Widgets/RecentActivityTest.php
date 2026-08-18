<?php

use App\Filament\Widgets\RecentActivity;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('shows recently updated tickets visible to the acting user, most recent first', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');
    $this->actingAs($user, 'web');

    $older = Ticket::factory()->create(['requester_id' => $user->id, 'updated_at' => now()->subDay()]);
    $newer = Ticket::factory()->create(['requester_id' => $user->id, 'updated_at' => now()]);
    // Someone else's ticket, updated most recently of all three — with only
    // ticket.view-own (not view-all), this must never appear, proving visibleTo()
    // scoping actually filters rather than the test passing merely because
    // the acting user happens to hold view-all (which would make scoping a no-op).
    $notVisible = Ticket::factory()->create(['updated_at' => now()->addSecond()]);

    Livewire::test(RecentActivity::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
        ->assertCanNotSeeTableRecords([$notVisible]);
});
