<?php

use App\Enums\TicketStatus;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('listing watchers requires view access to the ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $othersTicket = Ticket::factory()->create();
    $watcher = User::factory()->create();
    $ownTicket->watchers()->attach($watcher);

    $this->getJson("/api/v1/tickets/{$ownTicket->id}/watchers")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/tickets/{$othersTicket->id}/watchers")->assertForbidden();
});

test('adding a watcher requires view access to an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $watcher = User::factory()->create();

    $this->postJson("/api/v1/tickets/{$ownOpenTicket->id}/watchers", ['user_id' => $watcher->id])
        ->assertCreated();

    expect($ownOpenTicket->watchers()->pluck('users.id')->all())->toBe([$watcher->id]);

    $othersTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/v1/tickets/{$othersTicket->id}/watchers", ['user_id' => $watcher->id])
        ->assertForbidden();
});

test('removing a watcher requires view access to the ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $watcher = User::factory()->create();
    $ownOpenTicket->watchers()->attach($watcher);

    $this->deleteJson("/api/v1/tickets/{$ownOpenTicket->id}/watchers/{$watcher->id}")->assertNoContent();

    expect($ownOpenTicket->watchers()->count())->toBe(0);
});

test('a support agent (view-team, no update-own) can add a watcher to a team ticket', function () {
    $agent = User::factory()->create();
    Permission::findOrCreate('ticket.view-team');
    $agent->givePermissionTo('ticket.view-team');
    Sanctum::actingAs($agent, ['*']);

    $team = Team::factory()->create();
    $agent->teams()->attach($team);
    $ticket = Ticket::factory()->create([
        'team_id' => $team->id,
        'status' => TicketStatus::Open,
    ]);
    $watcher = User::factory()->create();

    $this->postJson("/api/v1/tickets/{$ticket->id}/watchers", ['user_id' => $watcher->id])
        ->assertCreated();

    expect($ticket->watchers()->pluck('users.id')->all())->toBe([$watcher->id]);
});
