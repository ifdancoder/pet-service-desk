<?php

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\SavedFilter;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('listing tickets requires the viewAny ability', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/tickets')->assertForbidden();
});

test('a user only sees tickets their permissions make visible', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $own = Ticket::factory()->create(['requester_id' => $user->id]);
    Ticket::factory()->create();

    $response = $this->getJson('/api/v1/tickets');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id)
        ->assertJsonPath('data.0.requester.id', $own->requester_id)
        ->assertJsonPath('data.0.category.id', $own->category_id)
        ->assertJsonPath('data.0.department.id', $own->department_id);
});

test('the status query param filters the visible list', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Sanctum::actingAs($user, ['*']);

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $response = $this->getJson('/api/v1/tickets?status=open');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $open->id);
});

test('an invalid status query param is rejected', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/tickets?status=not-a-status')->assertUnprocessable();
});

test('saved_filter_id loads the saved filters and direct params override them', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Sanctum::actingAs($user, ['*']);

    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();
    $otherTeam = Team::factory()->for($department)->create();
    $thirdTeam = Team::factory()->for($department)->create();

    $savedFilter = SavedFilter::factory()->for($user)->create([
        'filters' => ['team_id' => $team->id],
    ]);

    $matching = Ticket::factory()->create(['team_id' => $team->id]);
    Ticket::factory()->create(['team_id' => $thirdTeam->id]);

    $this->getJson("/api/v1/tickets?saved_filter_id={$savedFilter->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id);

    $overridden = Ticket::factory()->create(['team_id' => $otherTeam->id]);

    $this->getJson("/api/v1/tickets?saved_filter_id={$savedFilter->id}&team_id={$otherTeam->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $overridden->id);
});

test('a saved_filter_id belonging to another user is not found', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo('ticket.view-all');
    Sanctum::actingAs($user, ['*']);

    $savedFilter = SavedFilter::factory()->for($owner)->create();

    $this->getJson("/api/v1/tickets?saved_filter_id={$savedFilter->id}")->assertNotFound();
});

test('viewing a single ticket is gated by TicketPolicy::view', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $othersTicket = Ticket::factory()->create();

    $this->getJson("/api/v1/tickets/{$ownTicket->id}")
        ->assertOk()
        ->assertJsonPath('data.requester.id', $user->id)
        ->assertJsonPath('data.category.id', $ownTicket->category_id)
        ->assertJsonPath('data.department.id', $ownTicket->department_id)
        ->assertJsonPath('data.tags', []);
    $this->getJson("/api/v1/tickets/{$othersTicket->id}")->assertForbidden();
});
