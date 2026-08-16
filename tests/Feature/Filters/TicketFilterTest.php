<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filters\TicketFilter;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Ticket;

test('status filters tickets by exact status', function () {
    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $results = (new TicketFilter)->apply(Ticket::query(), ['status' => 'open'])->get();

    expect($results->pluck('id')->all())->toBe([$open->id]);
});

test('priority filters tickets by exact priority', function () {
    $high = Ticket::factory()->create(['priority' => TicketPriority::High]);
    Ticket::factory()->create(['priority' => TicketPriority::Low]);

    $results = (new TicketFilter)->apply(Ticket::query(), ['priority' => 'high'])->get();

    expect($results->pluck('id')->all())->toBe([$high->id]);
});

test('team_id filters tickets by team', function () {
    $team = Team::factory()->create();
    $matching = Ticket::factory()->create(['team_id' => $team->id]);
    Ticket::factory()->create(['team_id' => null]);

    $results = (new TicketFilter)->apply(Ticket::query(), ['team_id' => $team->id])->get();

    expect($results->pluck('id')->all())->toBe([$matching->id]);
});

test('tag filters tickets by tag slug', function () {
    $tag = Tag::factory()->create(['slug' => 'urgent']);
    $tagged = Ticket::factory()->create();
    $tagged->tags()->attach($tag);
    Ticket::factory()->create();

    $results = (new TicketFilter)->apply(Ticket::query(), ['tag' => 'urgent'])->get();

    expect($results->pluck('id')->all())->toBe([$tagged->id]);
});

test('multiple filters combine with AND semantics', function () {
    $team = Team::factory()->create();
    $matching = Ticket::factory()->create([
        'status' => TicketStatus::Open,
        'team_id' => $team->id,
    ]);
    Ticket::factory()->create(['status' => TicketStatus::Open, 'team_id' => null]);
    Ticket::factory()->create(['status' => TicketStatus::Closed, 'team_id' => $team->id]);

    $results = (new TicketFilter)->apply(Ticket::query(), [
        'status' => 'open',
        'team_id' => $team->id,
    ])->get();

    expect($results->pluck('id')->all())->toBe([$matching->id]);
});

test('an empty filter array returns every ticket unconstrained', function () {
    Ticket::factory()->count(3)->create();

    expect((new TicketFilter)->apply(Ticket::query(), [])->count())->toBe(3);
});
