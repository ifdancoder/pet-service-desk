<?php

use App\Enums\UserRole;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\RelationManagers\WatchersRelationManager;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::Administrator->value);
    $this->actingAs($this->staff, 'web');
});

test('attaches a watcher to a ticket', function () {
    $ticket = Ticket::factory()->create();
    $watcher = User::factory()->create();

    Livewire::test(WatchersRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $watcher->id])
        ->assertHasNoTableActionErrors();

    expect($ticket->watchers()->whereKey($watcher->id)->exists())->toBeTrue();
});

test('detaches a watcher from a ticket', function () {
    $ticket = Ticket::factory()->create();
    $watcher = User::factory()->create();
    $ticket->watchers()->attach($watcher);

    Livewire::test(WatchersRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callTableAction('detach', $watcher)
        ->assertHasNoTableActionErrors();

    expect($ticket->watchers()->whereKey($watcher->id)->exists())->toBeFalse();
});
