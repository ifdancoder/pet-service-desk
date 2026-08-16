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
    $user->givePermissionTo('ticket.assign');
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

test('close requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.close');
    $user->givePermissionTo('ticket.close');
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

test('reopen requires the permission and a closed ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.reopen');
    $user->givePermissionTo('ticket.reopen');
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

test('changePriority requires the permission and an open ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.change-priority');
    $user->givePermissionTo('ticket.change-priority');
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
