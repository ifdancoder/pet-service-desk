<?php

use App\DataTransferObjects\TicketData;
use App\Enums\TicketPriority;
use App\Events\TicketAssigned;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Support\Facades\Event;

test('creating a ticket sets sla_due_at from the applicable policy', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::High,
        'resolution_time_minutes' => 480,
        'active' => true,
    ]);

    $requester = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    $data = new TicketData(
        subject: 'Test',
        description: 'Test description',
        priority: TicketPriority::High,
        categoryId: $category->id,
        departmentId: $department->id,
        teamId: null,
        assigneeId: null,
    );

    $ticket = app(TicketService::class)->create($data, $requester);

    expect($ticket->sla_due_at)->not->toBeNull()
        ->and($ticket->sla_due_at->equalTo($ticket->created_at->addMinutes(480)))->toBeTrue();
});

test('creating a ticket with no applicable policy leaves sla_due_at null', function () {
    $requester = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    $data = new TicketData(
        subject: 'Test',
        description: 'Test description',
        priority: TicketPriority::Critical,
        categoryId: $category->id,
        departmentId: $department->id,
        teamId: null,
        assigneeId: null,
    );

    $ticket = app(TicketService::class)->create($data, $requester);

    expect($ticket->sla_due_at)->toBeNull();
});

test('changing priority recalculates sla_due_at anchored to created_at', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Low,
        'resolution_time_minutes' => 4320,
        'active' => true,
    ]);
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Critical,
        'resolution_time_minutes' => 120,
        'active' => true,
    ]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    $ticket = app(TicketService::class)->changePriority($ticket, TicketPriority::Critical);

    expect($ticket->sla_due_at->equalTo($ticket->created_at->addMinutes(120)))->toBeTrue();
});

test('updating a ticket recalculates sla_due_at when priority or category changes', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Low,
        'resolution_time_minutes' => 4320,
        'active' => true,
    ]);
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Critical,
        'resolution_time_minutes' => 120,
        'active' => true,
    ]);

    $ticket = app(TicketService::class)->create(new TicketData(
        subject: 'Original subject',
        description: 'Original description',
        priority: TicketPriority::Low,
        categoryId: TicketCategory::factory()->create()->id,
        departmentId: Department::factory()->create()->id,
        teamId: null,
        assigneeId: null,
    ), User::factory()->create());
    $originalSlaDueAt = $ticket->sla_due_at;

    $data = new TicketData(
        subject: $ticket->subject,
        description: $ticket->description,
        priority: TicketPriority::Critical,
        categoryId: $ticket->category_id,
        departmentId: $ticket->department_id,
        teamId: $ticket->team_id,
        assigneeId: $ticket->assignee_id,
    );

    $ticket = app(TicketService::class)->update($ticket, $data);

    expect($ticket->sla_due_at->equalTo($ticket->created_at->addMinutes(120)))->toBeTrue()
        ->and($ticket->sla_due_at->equalTo($originalSlaDueAt))->toBeFalse();
});

test('updating a ticket dispatches TicketAssigned when assignee_id changes to a new non-null user', function () {
    Event::fake();

    $ticket = Ticket::factory()->create(['assignee_id' => null]);
    $newAssignee = User::factory()->create();

    $data = new TicketData(
        subject: $ticket->subject,
        description: $ticket->description,
        priority: $ticket->priority,
        categoryId: $ticket->category_id,
        departmentId: $ticket->department_id,
        teamId: $ticket->team_id,
        assigneeId: $newAssignee->id,
    );

    app(TicketService::class)->update($ticket, $data);

    Event::assertDispatched(TicketAssigned::class, fn (TicketAssigned $event) => $event->ticket->is($ticket));
});

test('updating a ticket does not dispatch TicketAssigned when assignee_id is unchanged', function () {
    Event::fake();

    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    $data = new TicketData(
        subject: $ticket->subject,
        description: $ticket->description,
        priority: $ticket->priority,
        categoryId: $ticket->category_id,
        departmentId: $ticket->department_id,
        teamId: $ticket->team_id,
        assigneeId: $assignee->id,
    );

    app(TicketService::class)->update($ticket, $data);

    Event::assertNotDispatched(TicketAssigned::class);
});

test('updating a ticket does not dispatch TicketAssigned when assignee_id is set to null', function () {
    Event::fake();

    $assignee = User::factory()->create();
    $ticket = Ticket::factory()->create(['assignee_id' => $assignee->id]);

    $data = new TicketData(
        subject: $ticket->subject,
        description: $ticket->description,
        priority: $ticket->priority,
        categoryId: $ticket->category_id,
        departmentId: $ticket->department_id,
        teamId: $ticket->team_id,
        assigneeId: null,
    );

    app(TicketService::class)->update($ticket, $data);

    Event::assertNotDispatched(TicketAssigned::class);
});
