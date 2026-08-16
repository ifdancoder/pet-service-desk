<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('view-all sees every ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Ticket::factory()->count(3)->create();

    expect(Ticket::query()->visibleTo($user)->count())->toBe(3);
});

test('view-team sees only tickets on the user\'s teams', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();
    $otherTeam = Team::factory()->for($department)->create();

    $user = User::factory()->create();
    $user->teams()->attach($team);
    Permission::findOrCreate('ticket.view-team');
    $user->givePermissionTo('ticket.view-team');

    $inTeam = Ticket::factory()->create(['team_id' => $team->id]);
    Ticket::factory()->create(['team_id' => $otherTeam->id]);
    Ticket::factory()->create(['team_id' => null]);

    $results = Ticket::query()->visibleTo($user)->get();

    expect($results->pluck('id')->all())->toBe([$inTeam->id]);
});

test('view-own sees only the user\'s own requested tickets', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');

    $own = Ticket::factory()->create(['requester_id' => $user->id]);
    Ticket::factory()->create();

    $results = Ticket::query()->visibleTo($user)->get();

    expect($results->pluck('id')->all())->toBe([$own->id]);
});

test('view-team and view-own together union their results', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();

    $user = User::factory()->create();
    $user->teams()->attach($team);
    Permission::findOrCreate('ticket.view-team');
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo(['ticket.view-team', 'ticket.view-own']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $teamTicket = Ticket::factory()->create(['team_id' => $team->id]);
    Ticket::factory()->create();

    $results = Ticket::query()->visibleTo($user)->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$ownTicket->id, $teamTicket->id])->sort()->values()->all());
});

test('no view permissions means no visible tickets', function () {
    $user = User::factory()->create();
    Ticket::factory()->count(3)->create();

    expect(Ticket::query()->visibleTo($user)->count())->toBe(0);
});
