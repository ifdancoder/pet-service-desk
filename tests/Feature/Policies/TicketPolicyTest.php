<?php

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('viewAny is true when the user has any of the view permissions', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');

    expect($user->can('viewAny', Ticket::class))->toBeTrue();
});

test('viewAny is false with no view permissions', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', Ticket::class))->toBeFalse();
});

test('view-all permission grants view on any ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    $ticket = Ticket::factory()->create();

    expect($user->can('view', $ticket))->toBeTrue();
});

test('view-team permission grants view only when the user is in the ticket team', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();
    $otherTeam = Team::factory()->for($department)->create();

    $user = User::factory()->create();
    $user->teams()->attach($team);
    Permission::findOrCreate('ticket.view-team');
    $user->givePermissionTo('ticket.view-team');

    $ticketInTeam = Ticket::factory()->create(['team_id' => $team->id]);
    $ticketInOtherTeam = Ticket::factory()->create(['team_id' => $otherTeam->id]);

    expect($user->can('view', $ticketInTeam))->toBeTrue()
        ->and($user->can('view', $ticketInOtherTeam))->toBeFalse();
});

test('view-team permission does not grant view when the ticket has no team', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-team');
    $user->givePermissionTo('ticket.view-team');
    $ticket = Ticket::factory()->create(['team_id' => null]);

    expect($user->can('view', $ticket))->toBeFalse();
});

test("view-own permission grants view only on the user's own ticket", function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $otherTicket = Ticket::factory()->create();

    expect($user->can('view', $ownTicket))->toBeTrue()
        ->and($user->can('view', $otherTicket))->toBeFalse();
});

test('create requires the ticket.create permission', function () {
    $user = User::factory()->create();

    expect($user->can('create', Ticket::class))->toBeFalse();

    Permission::findOrCreate('ticket.create');
    $user->givePermissionTo('ticket.create');

    expect($user->can('create', Ticket::class))->toBeTrue();
});

test("update is only allowed for the open ticket's own requester with the permission", function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $ownClosedTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Closed,
    ]);
    $othersTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    expect($user->can('update', $ownOpenTicket))->toBeTrue()
        ->and($user->can('update', $ownClosedTicket))->toBeFalse()
        ->and($user->can('update', $othersTicket))->toBeFalse();
});

test('assign requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.assign');
    $user->givePermissionTo('ticket.assign');

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect($user->can('assign', $open))->toBeTrue()
        ->and($user->can('assign', $closed))->toBeFalse();
});

test('changePriority requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.change-priority');
    $user->givePermissionTo('ticket.change-priority');

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect($user->can('changePriority', $open))->toBeTrue()
        ->and($user->can('changePriority', $closed))->toBeFalse();
});

test('close requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.close');
    $user->givePermissionTo('ticket.close');

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect($user->can('close', $open))->toBeTrue()
        ->and($user->can('close', $closed))->toBeFalse();
});

test('reopen requires the permission and a closed ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.reopen');
    $user->givePermissionTo('ticket.reopen');

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect($user->can('reopen', $closed))->toBeTrue()
        ->and($user->can('reopen', $open))->toBeFalse();
});

test('delete requires the ticket.delete permission', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    expect($user->can('delete', $ticket))->toBeFalse();

    Permission::findOrCreate('ticket.delete');
    $user->givePermissionTo('ticket.delete');

    expect($user->can('delete', $ticket))->toBeTrue();
});
