<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('creating a ticket requires the ticket.create permission', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    // team_id/assignee_id deliberately omitted: this user only ever holds
    // ticket.create (not ticket.assign), and per the ProhibitedWithoutPermission
    // rule, those keys aren't allowed in the payload at all without it, even
    // set explicitly to null. See the dedicated C2 tests below.
    $payload = [
        'subject' => 'Printer on fire',
        'description' => 'Smoke coming out of the printer.',
        'priority' => 'critical',
        'category_id' => $category->id,
        'department_id' => $department->id,
    ];

    $this->postJson('/api/v1/tickets', $payload)->assertForbidden();

    Permission::findOrCreate('ticket.create');
    $user->givePermissionTo('ticket.create');

    $response = $this->postJson('/api/v1/tickets', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.subject', 'Printer on fire')
        ->assertJsonPath('data.requester_id', $user->id)
        ->assertJsonPath('data.status', 'open');
});

test('creating a ticket rejects an inactive category', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.create');
    $user->givePermissionTo('ticket.create');
    Sanctum::actingAs($user, ['*']);

    $category = TicketCategory::factory()->inactive()->create();
    $department = Department::factory()->create();

    $this->postJson('/api/v1/tickets', [
        'subject' => 'Subject',
        'description' => 'Description',
        'priority' => 'normal',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'team_id' => null,
        'assignee_id' => null,
    ])->assertUnprocessable();
});

test("update is only allowed for the open ticket's own requester with the permission", function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $othersTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    // priority/team_id/assignee_id deliberately omitted: this user only holds
    // ticket.update-own (a customer-shaped grant), not ticket.change-priority
    // or ticket.assign, so those fields must stay out of the payload. See
    // TicketStoreUpdateDestroyTest's C2 coverage below for the permission and
    // field-preservation behavior itself.
    $payload = [
        'subject' => 'Updated subject',
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
    ];

    $this->putJson("/api/v1/tickets/{$ownTicket->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.subject', 'Updated subject');

    $this->putJson("/api/v1/tickets/{$othersTicket->id}", $payload)->assertForbidden();
});

test('delete requires the ticket.delete permission', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $ticket = Ticket::factory()->create();

    $this->deleteJson("/api/v1/tickets/{$ticket->id}")->assertForbidden();

    Permission::findOrCreate('ticket.delete');
    $user->givePermissionTo('ticket.delete');

    $this->deleteJson("/api/v1/tickets/{$ticket->id}")->assertNoContent();
    expect(Ticket::find($ticket->id))->toBeNull();
});

test('creating a ticket with an assignee_id without ticket.assign is rejected', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.create');
    $user->givePermissionTo('ticket.create');
    Sanctum::actingAs($user, ['*']);

    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();
    $assignee = User::factory()->create();

    $this->postJson('/api/v1/tickets', [
        'subject' => 'Subject',
        'description' => 'Description',
        'priority' => 'normal',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'team_id' => null,
        'assignee_id' => $assignee->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('assignee_id');
});

test('creating a ticket with a team_id and assignee_id succeeds when the user holds ticket.assign', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.create');
    Permission::findOrCreate('ticket.assign');
    $user->givePermissionTo(['ticket.create', 'ticket.assign']);
    Sanctum::actingAs($user, ['*']);

    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();
    $team = Team::factory()->create();
    $assignee = User::factory()->create();

    $response = $this->postJson('/api/v1/tickets', [
        'subject' => 'Subject',
        'description' => 'Description',
        'priority' => 'normal',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'team_id' => $team->id,
        'assignee_id' => $assignee->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.team_id', $team->id)
        ->assertJsonPath('data.assignee_id', $assignee->id);
});

test('updating a ticket with a team_id without ticket.assign is rejected', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $team = Team::factory()->create();

    $this->putJson("/api/v1/tickets/{$ownTicket->id}", [
        'subject' => $ownTicket->subject,
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
        'team_id' => $team->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('team_id');
});

test('updating a ticket with a priority without ticket.change-priority is rejected', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Normal,
    ]);

    $this->putJson("/api/v1/tickets/{$ownTicket->id}", [
        'subject' => $ownTicket->subject,
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
        'priority' => 'critical',
    ])->assertUnprocessable()->assertJsonValidationErrors('priority');
});

test('updating a ticket with team_id/assignee_id/priority succeeds when the user holds the relevant permissions', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    Permission::findOrCreate('ticket.assign');
    Permission::findOrCreate('ticket.change-priority');
    $user->givePermissionTo(['ticket.update-own', 'ticket.assign', 'ticket.change-priority']);
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
        'priority' => TicketPriority::Normal,
    ]);
    $team = Team::factory()->create();
    $assignee = User::factory()->create();

    $response = $this->putJson("/api/v1/tickets/{$ownTicket->id}", [
        'subject' => $ownTicket->subject,
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
        'team_id' => $team->id,
        'assignee_id' => $assignee->id,
        'priority' => 'critical',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.team_id', $team->id)
        ->assertJsonPath('data.assignee_id', $assignee->id)
        ->assertJsonPath('data.priority', 'critical');
});

test('updating only subject/description preserves the existing assignee_id and team_id', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $team = Team::factory()->create();
    $assignee = User::factory()->create();
    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
        'team_id' => $team->id,
        'assignee_id' => $assignee->id,
    ]);

    $response = $this->putJson("/api/v1/tickets/{$ownTicket->id}", [
        'subject' => 'Only the subject changed',
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.subject', 'Only the subject changed')
        ->assertJsonPath('data.team_id', $team->id)
        ->assertJsonPath('data.assignee_id', $assignee->id);

    expect($ownTicket->fresh())
        ->team_id->toBe($team->id)
        ->assignee_id->toBe($assignee->id);
});

test('updating a ticket with explicit null assignee_id/team_id without ticket.assign is rejected and does not clear them', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $team = Team::factory()->create();
    $assignee = User::factory()->create();
    $ownTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
        'team_id' => $team->id,
        'assignee_id' => $assignee->id,
    ]);

    $this->putJson("/api/v1/tickets/{$ownTicket->id}", [
        'subject' => 'Still just the subject',
        'description' => $ownTicket->description,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
        'assignee_id' => null,
        'team_id' => null,
    ])->assertUnprocessable()->assertJsonValidationErrors(['assignee_id', 'team_id']);

    expect($ownTicket->fresh())
        ->team_id->toBe($team->id)
        ->assignee_id->toBe($assignee->id);
});
