<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('assign requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    Permission::findOrCreate('ticket.assign');
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo(['ticket.assign', 'ticket.view-all']);
    Sanctum::actingAs($user, ['*']);

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->postJson("/api/v1/tickets/{$open->id}/assign", ['assignee_id' => $assignee->id])
        ->assertOk()
        ->assertJsonPath('data.assignee_id', $assignee->id);

    $this->postJson("/api/v1/tickets/{$closed->id}/assign", ['assignee_id' => $assignee->id])
        ->assertForbidden();

    $userWithoutPermission = User::factory()->create();
    Sanctum::actingAs($userWithoutPermission, ['*']);
    $anotherOpen = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/v1/tickets/{$anotherOpen->id}/assign", ['assignee_id' => $assignee->id])
        ->assertForbidden();
});

test('assign requires view access even with the ticket.assign permission', function () {
    $teamLead = User::factory()->create();
    Permission::findOrCreate('ticket.assign');
    $teamLead->givePermissionTo('ticket.assign');
    Sanctum::actingAs($teamLead, ['*']);

    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/v1/tickets/{$ticket->id}/assign", ['assignee_id' => $assignee->id])
        ->assertForbidden();
});

test('close requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.close');
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo(['ticket.close', 'ticket.view-all']);
    Sanctum::actingAs($user, ['*']);

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->postJson("/api/v1/tickets/{$open->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    $this->postJson("/api/v1/tickets/{$closed->id}/close")->assertForbidden();

    $userWithoutPermission = User::factory()->create();
    Sanctum::actingAs($userWithoutPermission, ['*']);
    $anotherOpen = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/v1/tickets/{$anotherOpen->id}/close")->assertForbidden();
});

test('close requires view access even with the ticket.close permission', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.close');
    $user->givePermissionTo('ticket.close');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/v1/tickets/{$ticket->id}/close")->assertForbidden();
});

test('reopen requires the permission and a closed ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.reopen');
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo(['ticket.reopen', 'ticket.view-all']);
    Sanctum::actingAs($user, ['*']);

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->postJson("/api/v1/tickets/{$closed->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.status', 'open');

    $this->postJson("/api/v1/tickets/{$open->id}/reopen")->assertForbidden();

    $userWithoutPermission = User::factory()->create();
    Sanctum::actingAs($userWithoutPermission, ['*']);
    $anotherClosed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->postJson("/api/v1/tickets/{$anotherClosed->id}/reopen")->assertForbidden();
});

test('reopen requires view access even with the ticket.reopen permission', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.reopen');
    $user->givePermissionTo('ticket.reopen');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->postJson("/api/v1/tickets/{$ticket->id}/reopen")->assertForbidden();
});

test('changePriority requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.change-priority');
    Permission::findOrCreate('ticket.view-all');
    $user->givePermissionTo(['ticket.change-priority', 'ticket.view-all']);
    Sanctum::actingAs($user, ['*']);

    $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $closed = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    $this->patchJson("/api/v1/tickets/{$open->id}/priority", ['priority' => 'critical'])
        ->assertOk()
        ->assertJsonPath('data.priority', 'critical');

    $this->patchJson("/api/v1/tickets/{$closed->id}/priority", ['priority' => 'critical'])
        ->assertForbidden();

    $userWithoutPermission = User::factory()->create();
    Sanctum::actingAs($userWithoutPermission, ['*']);
    $anotherOpen = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->patchJson("/api/v1/tickets/{$anotherOpen->id}/priority", ['priority' => 'critical'])
        ->assertForbidden();
});

test('changePriority requires view access even with the ticket.change-priority permission', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.change-priority');
    $user->givePermissionTo('ticket.change-priority');
    Sanctum::actingAs($user, ['*']);

    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->patchJson("/api/v1/tickets/{$ticket->id}/priority", ['priority' => 'critical'])
        ->assertForbidden();
});
