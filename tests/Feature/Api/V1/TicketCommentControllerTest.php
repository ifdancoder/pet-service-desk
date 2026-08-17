<?php

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('listing comments requires view access to the parent ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $othersTicket = Ticket::factory()->create();
    TicketComment::factory()->for($ownTicket)->create();

    $this->getJson("/api/v1/tickets/{$ownTicket->id}/comments")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/tickets/{$othersTicket->id}/comments")->assertForbidden();
});

test('creating a comment requires comment.create and view access to the ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.create');
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo(['comment.create', 'ticket.view-own']);
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $othersTicket = Ticket::factory()->create();

    $this->postJson("/api/v1/tickets/{$ownTicket->id}/comments", ['body' => 'On it.'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'On it.')
        ->assertJsonPath('data.author_id', $user->id);

    $this->postJson("/api/v1/tickets/{$othersTicket->id}/comments", ['body' => 'On it.'])
        ->assertForbidden();
});

test('delete-own only allows deleting the user\'s own comment', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.delete-own');
    $user->givePermissionTo('comment.delete-own');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create();
    $ownComment = TicketComment::factory()->for($ticket)->create(['author_id' => $user->id]);
    $othersComment = TicketComment::factory()->for($ticket)->create();

    $this->deleteJson("/api/v1/tickets/{$ticket->id}/comments/{$othersComment->id}")->assertForbidden();
    $this->deleteJson("/api/v1/tickets/{$ticket->id}/comments/{$ownComment->id}")->assertNoContent();
    expect(TicketComment::find($ownComment->id))->toBeNull();
});

test('a customer does not see internal comments on their own ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    TicketComment::factory()->for($ownTicket)->create(['is_internal' => false]);
    TicketComment::factory()->for($ownTicket)->create(['is_internal' => true]);

    $response = $this->getJson("/api/v1/tickets/{$ownTicket->id}/comments");

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.is_internal'))->toBeFalse();
});

test('a support agent sees internal comments via ticket.view-team', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-team');
    $user->givePermissionTo('ticket.view-team');
    Sanctum::actingAs($user, ['*']);

    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $ticket = Ticket::factory()->create(['team_id' => $team->id]);
    TicketComment::factory()->for($ticket)->create(['is_internal' => false]);
    TicketComment::factory()->for($ticket)->create(['is_internal' => true]);

    $this->getJson("/api/v1/tickets/{$ticket->id}/comments")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('a customer gets 422 posting a comment with is_internal true', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.create');
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo(['comment.create', 'ticket.view-own']);
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);

    $this->postJson("/api/v1/tickets/{$ownTicket->id}/comments", [
        'body' => 'Trying to plant an internal note',
        'is_internal' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('is_internal');
});

test('a support agent can post an internal comment', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.create');
    Permission::findOrCreate('ticket.view-team');
    $user->givePermissionTo(['comment.create', 'ticket.view-team']);
    Sanctum::actingAs($user, ['*']);

    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $ticket = Ticket::factory()->create(['team_id' => $team->id]);

    $this->postJson("/api/v1/tickets/{$ticket->id}/comments", [
        'body' => 'Internal note for the team',
        'is_internal' => true,
    ])->assertCreated()->assertJsonPath('data.is_internal', true);
});
