<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\TicketStatsOverview;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
});

test('shows counts scoped to what the acting user can see', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->assignRole(UserRole::Customer->value);
    $user->givePermissionTo('ticket.view-own');

    Ticket::factory()->create(['requester_id' => $user->id, 'status' => TicketStatus::Open, 'priority' => TicketPriority::High]);
    Ticket::factory()->create(['requester_id' => $user->id, 'status' => TicketStatus::Closed]);
    // Someone else's ticket shares the SAME status and priority as the user's own
    // visible ticket. If visibleTo() scoping were broken (unscoped), the Open/High
    // counts would both show 2 instead of 1, so this genuinely proves scoping works
    // instead of coincidentally passing regardless of it.
    Ticket::factory()->create(['status' => TicketStatus::Open, 'priority' => TicketPriority::High]);

    $this->actingAs($user, 'web');

    $html = Livewire::test(TicketStatsOverview::class)->html();

    // Extract all stat values from the rendered output
    preg_match_all('/fi-wi-stats-overview-stat-value[^>]*>\s*(\d+)\s*</', $html, $matches);
    $statValues = $matches[1] ?? [];

    // With broken scoping (Ticket::query() without visibleTo), we'd count all tickets
    // With correct scoping, we should only count what the user can see
    expect(in_array('1', $statValues))->toBeTrue();
    expect(in_array('2', $statValues))->toBeFalse();
});
