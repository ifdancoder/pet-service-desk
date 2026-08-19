<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

// The success path (real matches coming back from Elasticsearch) needs a
// running Elasticsearch instance and isn't covered here. What's testable
// without one is everything that happens before TicketSearchService is
// ever reached: authorization and validation both short-circuit in
// SearchTicketsRequest before the controller touches the search client.

test('search requires a ticket view permission', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/tickets/search?q=printer')->assertForbidden();
});

test('search requires the q parameter', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/tickets/search')->assertUnprocessable();
});

test('search rejects a query shorter than 2 characters', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/tickets/search?q=a')->assertUnprocessable();
});
