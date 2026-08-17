<?php

use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('a user with view access can sync tags onto a ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
    $tag = Tag::factory()->create();

    $response = $this->putJson("/api/v1/tickets/{$ticket->id}/tags", [
        'tags' => [$tag->slug],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', $tag->slug);

    expect($ticket->fresh()->tags->pluck('slug')->all())->toBe([$tag->slug]);
});

test('syncing tags replaces the existing set rather than appending', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
    $firstTag = Tag::factory()->create();
    $secondTag = Tag::factory()->create();
    $ticket->tags()->attach([$firstTag->id, $secondTag->id]);

    $newTag = Tag::factory()->create();

    $this->putJson("/api/v1/tickets/{$ticket->id}/tags", [
        'tags' => [$newTag->slug],
    ])->assertOk();

    expect($ticket->fresh()->tags->pluck('slug')->all())->toBe([$newTag->slug]);
});

test('syncing to an empty array removes all tags', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['requester_id' => $user->id]);
    $tag = Tag::factory()->create();
    $ticket->tags()->attach($tag);

    $this->putJson("/api/v1/tickets/{$ticket->id}/tags", [
        'tags' => [],
    ])->assertOk()->assertJsonCount(0, 'data');

    expect($ticket->fresh()->tags)->toHaveCount(0);
});

test('a user without view access on the ticket is forbidden from syncing tags', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $othersTicket = Ticket::factory()->create();
    $tag = Tag::factory()->create();

    $this->putJson("/api/v1/tickets/{$othersTicket->id}/tags", [
        'tags' => [$tag->slug],
    ])->assertForbidden();
});

test('tagging a ticket via the sync endpoint makes it reachable through the tag filter', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create();
    $otherTicket = Ticket::factory()->create();
    $tag = Tag::factory()->create();

    $this->putJson("/api/v1/tickets/{$ticket->id}/tags", [
        'tags' => [$tag->slug],
    ])->assertOk();

    $this->getJson("/api/v1/tickets?tag={$tag->slug}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ticket->id);
});
