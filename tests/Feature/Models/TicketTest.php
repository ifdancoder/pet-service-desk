<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;

test('a ticket belongs to a requester, assignee, category, department, and team', function () {
    $requester = User::factory()->create();
    $assignee = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'category_id' => $category->id,
        'department_id' => $department->id,
        'team_id' => $team->id,
    ]);

    expect($ticket->requester->is($requester))->toBeTrue()
        ->and($ticket->assignee->is($assignee))->toBeTrue()
        ->and($ticket->category->is($category))->toBeTrue()
        ->and($ticket->department->is($department))->toBeTrue()
        ->and($ticket->team->is($team))->toBeTrue();
});

test('status and priority are cast to their enums', function () {
    $ticket = Ticket::factory()->create([
        'status' => TicketStatus::InProgress,
        'priority' => TicketPriority::Critical,
    ]);

    expect($ticket->status)->toBe(TicketStatus::InProgress)
        ->and($ticket->priority)->toBe(TicketPriority::Critical);
});

test('the open scope excludes closed tickets', function () {
    Ticket::factory()->create(['status' => TicketStatus::Open]);
    Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect(Ticket::open()->count())->toBe(1);
});

test('the closed scope only includes closed tickets', function () {
    Ticket::factory()->create(['status' => TicketStatus::Open]);
    Ticket::factory()->create(['status' => TicketStatus::Closed]);

    expect(Ticket::closed()->count())->toBe(1);
});

test('a user can list tickets they requested and tickets assigned to them', function () {
    $user = User::factory()->create();

    $requested = Ticket::factory()->create(['requester_id' => $user->id]);
    $assigned = Ticket::factory()->create(['assignee_id' => $user->id]);

    expect($user->requestedTickets()->pluck('id')->all())->toBe([$requested->id])
        ->and($user->assignedTickets()->pluck('id')->all())->toBe([$assigned->id]);
});

test('a category has many tickets', function () {
    $category = TicketCategory::factory()->create();
    $ticket = Ticket::factory()->create(['category_id' => $category->id]);

    expect($category->tickets()->pluck('id')->all())->toBe([$ticket->id]);
});
