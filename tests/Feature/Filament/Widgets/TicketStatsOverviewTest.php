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
    Ticket::factory()->create(); // someone else's ticket — must not count

    $this->actingAs($user, 'web');

    Livewire::test(TicketStatsOverview::class)
        ->assertSee('1'); // the one Open ticket belonging to $user
});
