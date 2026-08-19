<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('create requires the comment.create permission and the ability to view the ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    expect($user->can('create', [TicketComment::class, $ticket]))->toBeFalse();

    Permission::findOrCreate('comment.create');
    $user->givePermissionTo('comment.create');

    // Has comment.create but no view-* permission at all, still denied.
    expect($user->can('create', [TicketComment::class, $ticket]))->toBeFalse();

    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');

    // Still denied: comment.create + view-own permission, but this ticket isn't theirs.
    expect($user->can('create', [TicketComment::class, $ticket]))->toBeFalse();

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);

    // Now allowed: comment.create + can actually view this specific ticket (it's their own).
    expect($user->can('create', [TicketComment::class, $ownTicket]))->toBeTrue();
});

test('delete-any permission allows deleting any comment', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.delete-any');
    $user->givePermissionTo('comment.delete-any');

    $comment = TicketComment::factory()->create();

    expect($user->can('delete', $comment))->toBeTrue();
});

test("delete-own permission only allows deleting the user's own comment", function () {
    $user = User::factory()->create();
    Permission::findOrCreate('comment.delete-own');
    $user->givePermissionTo('comment.delete-own');

    $ownComment = TicketComment::factory()->create(['author_id' => $user->id]);
    $othersComment = TicketComment::factory()->create();

    expect($user->can('delete', $ownComment))->toBeTrue()
        ->and($user->can('delete', $othersComment))->toBeFalse();
});

test('no delete permission denies deleting any comment, including own', function () {
    $user = User::factory()->create();
    $ownComment = TicketComment::factory()->create(['author_id' => $user->id]);

    expect($user->can('delete', $ownComment))->toBeFalse();
});
