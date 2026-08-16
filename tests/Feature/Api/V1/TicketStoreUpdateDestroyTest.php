<?php

use App\Enums\TicketStatus;
use App\Models\Department;
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

    $payload = [
        'subject' => 'Printer on fire',
        'description' => 'Smoke coming out of the printer.',
        'priority' => 'critical',
        'category_id' => $category->id,
        'department_id' => $department->id,
        'team_id' => null,
        'assignee_id' => null,
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

    $payload = [
        'subject' => 'Updated subject',
        'description' => $ownTicket->description,
        'priority' => $ownTicket->priority->value,
        'category_id' => $ownTicket->category_id,
        'department_id' => $ownTicket->department_id,
        'team_id' => $ownTicket->team_id,
        'assignee_id' => $ownTicket->assignee_id,
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
