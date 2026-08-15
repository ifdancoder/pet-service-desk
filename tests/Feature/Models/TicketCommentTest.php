<?php

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

test('a ticket has many comments', function () {
    $ticket = Ticket::factory()->create();
    $comments = TicketComment::factory()->count(2)->for($ticket)->create();

    expect($ticket->comments()->pluck('id')->sort()->values()->all())
        ->toBe($comments->pluck('id')->sort()->values()->all());
});

test('a comment belongs to a ticket and an author', function () {
    $ticket = Ticket::factory()->create();
    $author = User::factory()->create();

    $comment = TicketComment::factory()->for($ticket)->create([
        'author_id' => $author->id,
    ]);

    expect($comment->ticket->is($ticket))->toBeTrue()
        ->and($comment->author->is($author))->toBeTrue();
});

test('the internal state marks a comment as not requester-visible', function () {
    $comment = TicketComment::factory()->internal()->create();

    expect($comment->is_internal)->toBeTrue();
});
