<?php

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
