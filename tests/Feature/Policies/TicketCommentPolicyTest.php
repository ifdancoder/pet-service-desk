<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('create requires the comment.create permission', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->create();

    expect($user->can('create', [TicketComment::class, $ticket]))->toBeFalse();

    Permission::findOrCreate('comment.create');
    $user->givePermissionTo('comment.create');

    expect($user->can('create', [TicketComment::class, $ticket]))->toBeTrue();
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
