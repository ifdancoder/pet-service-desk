<?php

use App\Models\Ticket;
use App\Models\User;

test('a ticket can have many watchers', function () {
    $ticket = Ticket::factory()->create();
    $watchers = User::factory()->count(2)->create();

    $ticket->watchers()->attach($watchers);

    expect($ticket->watchers()->pluck('users.id')->sort()->values()->all())
        ->toBe($watchers->pluck('id')->sort()->values()->all());
});

test('a user can watch multiple tickets', function () {
    $user = User::factory()->create();
    $tickets = Ticket::factory()->count(2)->create();

    $user->watchedTickets()->attach($tickets);

    expect($user->watchedTickets()->pluck('tickets.id')->sort()->values()->all())
        ->toBe($tickets->pluck('id')->sort()->values()->all());
});
