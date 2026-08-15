<?php

use App\Models\Tag;
use App\Models\Ticket;

test('a ticket can have many tags', function () {
    $ticket = Ticket::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $ticket->tags()->attach($tags);

    expect($ticket->tags()->pluck('tags.id')->sort()->values()->all())
        ->toBe($tags->pluck('id')->sort()->values()->all());
});

test('a tag can be attached to many tickets', function () {
    $tag = Tag::factory()->create();
    $tickets = Ticket::factory()->count(2)->create();

    $tag->tickets()->attach($tickets);

    expect($tag->tickets()->pluck('tickets.id')->sort()->values()->all())
        ->toBe($tickets->pluck('id')->sort()->values()->all());
});
