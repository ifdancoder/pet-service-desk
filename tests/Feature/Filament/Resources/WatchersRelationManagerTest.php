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

test('a support_manager without user.manage can still see and use the watchers tab', function () {
    // Regression: canViewForRecord() used to fall through to the parent
    // implementation, which authorizes the RELATED model (UserPolicy::viewAny,
    // gated on 'user.manage', administrator only), hiding the tab from every
    // other staff role. It must authorize the OWNER ticket instead.
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::SupportManager->value);
    expect($manager->can('user.manage'))->toBeFalse();
    $this->actingAs($manager, 'web');

    $ticket = Ticket::factory()->create();
    $watcher = User::factory()->create();

    Livewire::test(WatchersRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->assertSuccessful()
        ->callTableAction('attach', data: ['recordId' => $watcher->id])
        ->assertHasNoTableActionErrors();

    expect($ticket->watchers()->whereKey($watcher->id)->exists())->toBeTrue();
});

test('a user who cannot view the owner ticket cannot see the watchers tab', function () {
    $outsider = User::factory()->create();
    $outsider->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($outsider, 'web');

    $ticket = Ticket::factory()->create();

    // canViewForRecord() is what the owning Edit page consults to decide
    // whether to render the tab at all (HasRelationManagers::getRelationManagers()).
    expect(WatchersRelationManager::canViewForRecord($ticket, EditTicket::class))->toBeFalse();
});
