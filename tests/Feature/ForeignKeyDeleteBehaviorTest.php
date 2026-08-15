<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Database\QueryException;

test('deleting a ticket cascades to delete its comments', function () {
    $ticket = Ticket::factory()->create();
    TicketComment::factory()->for($ticket)->create();

    $ticket->delete();

    expect(TicketComment::count())->toBe(0);
});

test('deleting a department with no tickets cascades to delete its teams', function () {
    $department = Department::factory()->create();
    $teams = Team::factory()->count(2)->for($department)->create();

    $department->delete();

    expect(Team::whereIn('id', $teams->pluck('id'))->count())->toBe(0);
});

test('deleting a ticket assignee nulls out the ticket assignee_id instead of deleting the ticket', function () {
    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    $assignee->delete();

    $ticket = $ticket->fresh();

    expect($ticket)->not->toBeNull()
        ->and($ticket->assignee_id)->toBeNull();
});

test('deleting a ticket category referenced by a ticket throws a database exception', function () {
    $category = TicketCategory::factory()->create();
    Ticket::factory()->create(['category_id' => $category->id]);

    expect(fn () => $category->delete())->toThrow(QueryException::class);
});

test('deleting a department referenced by a ticket throws a database exception', function () {
    $department = Department::factory()->create();
    Ticket::factory()->create(['department_id' => $department->id]);

    expect(fn () => $department->delete())->toThrow(QueryException::class);
});
